<?php

namespace Dancycodes\Hyper\View\Fragment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * Blade Fragment Renderer
 *
 * Renders specific fragments from Blade views by parsing template content and extracting
 * named sections defined with fragment directives. Enables partial view rendering for
 * reactive updates where entire view re-rendering is unnecessary.
 *
 * Fragments are defined in Blade templates using paired directives (fragment/endfragment)
 * with unique names. Parser locates fragment boundaries, extracts content between opening
 * and closing directives, and renders extracted content through Blade compiler with
 * provided data.
 *
 * Implements automatic cache recovery mechanism for intermittent Blade compilation errors
 * related to directive registration. Clears view cache and retries rendering on compilation
 * failures to handle race conditions during application bootstrapping.
 *
 * @see \Dancycodes\Hyper\View\Fragment\BladeFragmentParser
 * @see \Dancycodes\Hyper\Http\HyperResponse::fragment()
 */
class BladeFragment
{
    /** Blade directive name for fragment opening tag */
    public const OPEN = 'fragment';

    /** Blade directive name for fragment closing tag */
    public const CLOSE = 'endfragment';

    /**
     * Render named fragment from Blade view with data
     *
     * Resolves view path, reads template content, parses for fragment boundaries,
     * extracts fragment content, and renders through Blade compiler. Automatically
     * attempts cache recovery if Blade compilation error occurs.
     *
     * @param string $view Blade view name in dot notation
     * @param string $fragment Fragment name to extract and render
     * @param array<string, mixed> $data Variables to pass to Blade compiler
     *
     * @throws \RuntimeException When fragment not found or cache recovery fails
     *
     * @return string Rendered fragment HTML
     */
    public static function render(string $view, string $fragment, array $data = []): string
    {
        try {
            return self::renderFragment($view, $fragment, $data);
        } catch (Throwable $e) {
            if (self::isBladeCompilationError($e)) {
                return self::renderWithCacheRecovery($view, $fragment, $data, $e);
            }

            throw $e;
        }
    }

    /**
     * Execute fragment rendering operation
     *
     * Resolves view instance, reads template file, parses content for fragment,
     * and compiles extracted content through Blade renderer with data variables.
     *
     * @param string $view Blade view name in dot notation
     * @param string $fragment Fragment name to extract
     * @param array<string, mixed> $data Blade compiler variables
     *
     * @return string Rendered fragment HTML
     */
    private static function renderFragment(string $view, string $fragment, array $data = []): string
    {
        /** @var \Illuminate\View\View $viewInstance */
        $viewInstance = View::make($view, $data);
        $path = $viewInstance->getPath();
        $content = File::get($path);
        $output = self::captureFragmentFromContent($fragment, $path, $content);

        return Blade::render($output, $data);
    }

    /**
     * Attempt fragment rendering after clearing view cache
     *
     * Handles intermittent cache corruption when fragment directives are not yet
     * registered during initial compilation. Clears view cache, logs warning,
     * and retries rendering. Throws detailed exception if retry fails.
     *
     * @param string $view Blade view name
     * @param string $fragment Fragment name
     * @param array<string, mixed> $data Blade compiler variables
     * @param Throwable $originalError Original compilation error
     *
     * @throws \RuntimeException When cache clearing does not resolve error
     *
     * @return string Rendered fragment HTML
     */
    private static function renderWithCacheRecovery(string $view, string $fragment, array $data, Throwable $originalError): string
    {
        try {
            Artisan::call('view:clear');

            Log::warning('Hyper: Cleared view cache due to fragment rendering error', [
                'view' => $view,
                'fragment' => $fragment,
                'error' => $originalError->getMessage(),
            ]);

            return self::renderFragment($view, $fragment, $data);
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Failed to render fragment '{$fragment}' in view '{$view}'. " .
                'This may indicate fragment directives are not properly registered. ' .
                "Try running: php artisan view:clear\n\n" .
                "Original error: {$originalError->getMessage()}\n" .
                "Retry error: {$e->getMessage()}",
                0,
                $originalError
            );
        }
    }

    /**
     * Determine if exception is Blade compilation error
     *
     * Checks exception message for common Blade compilation error patterns including
     * parse errors, syntax errors, unexpected EOF, and fragment directive mentions.
     * Used to identify recoverable errors versus application logic errors.
     *
     * @param Throwable $e Exception to analyze
     *
     * @return bool True if error appears to be Blade compilation related
     */
    private static function isBladeCompilationError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unexpected end of file') ||
               str_contains($message, 'syntax error') ||
               str_contains($message, 'parse error') ||
               str_contains($message, 'fragment') ||
               str_contains($message, 'endfragment');
    }

    /**
     * Extract fragment content from view template
     *
     * Parses template content to locate fragment boundaries, validates fragment exists
     * and is unique, handles nested fragments by tracking open/close depth, and extracts
     * content between matched opening and closing directive pair.
     *
     * @param string $fragment Fragment name to extract
     * @param string $path View file path for error messages
     * @param string $content Template content to parse
     *
     * @throws \RuntimeException When fragment not found, duplicated, or malformed
     *
     * @return string Extracted fragment content without directive tags
     */
    private static function captureFragmentFromContent(string $fragment, string $path, string $content): string
    {
        $parser = new BladeFragmentParser(self::OPEN, self::CLOSE);
        $nodes = $parser->parse($content);

        $node = array_filter($nodes, function (OpenFragmentElement|CloseFragmentElement $node) use ($fragment) {
            return $node instanceof OpenFragmentElement && $node->name === $fragment;
        });

        throw_if(empty($node), "No fragment called \"{$fragment}\" exists in \"{$path}\"");
        throw_if(count($node) > 1, "Multiple fragments called \"{$fragment}\" exists in \"{$path}\"");

        $nestedOccurrences = 0;
        $openElement = null;
        $closeElement = null;

        foreach ($nodes as $node) {
            if ($openElement === null && $node instanceof OpenFragmentElement) {
                if ($node->name === $fragment) {
                    $openElement = $node;

                    continue;
                }
            }

            if ($openElement !== null && $node instanceof OpenFragmentElement) {
                $nestedOccurrences++;

                continue;
            }

            if ($openElement !== null && $node instanceof CloseFragmentElement) {
                if ($nestedOccurrences === 0) {
                    $closeElement = $node;
                    break;
                } else {
                    $nestedOccurrences--;
                }
            }
        }

        if ($openElement === null || $closeElement === null) {
            throw new \RuntimeException("Fragment structure error: could not find opening or closing element for '{$fragment}' in '{$path}'");
        }

        return mb_substr(
            $content,
            $openElement->endOffset,
            $closeElement->startOffset - $openElement->endOffset
        );
    }
}
