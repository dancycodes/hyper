<?php

namespace Dancycodes\Hyper\View\Fragment;

/**
 * Blade Fragment Directive Parser
 *
 * Parses Blade template content to locate and extract fragment directive boundaries
 * using regular expression pattern matching. Identifies opening directives with names
 * and closing directives, computing multibyte-safe character offsets for content
 * extraction.
 *
 * Handles escaped directive syntax (@@ prefix) by excluding from matches, normalizes
 * line endings for consistent parsing across platforms, and converts byte offsets from
 * regex engine to multibyte character offsets for Unicode template support.
 *
 * Returns collection of FragmentElement instances representing directive locations
 * with start/end offsets for precise content slicing during fragment rendering.
 *
 * @see \Dancycodes\Hyper\View\Fragment\BladeFragment
 * @see \Dancycodes\Hyper\View\Fragment\OpenFragmentElement
 * @see \Dancycodes\Hyper\View\Fragment\CloseFragmentElement
 */
class BladeFragmentParser
{
    /**
     * Initialize parser with directive names
     *
     * @param string $openDirective Opening directive name (e.g., 'fragment')
     * @param string $closeDirective Closing directive name (e.g., 'endfragment')
     */
    public function __construct(private string $openDirective, private string $closeDirective) {}

    /**
     * Parse template content to extract fragment directive locations
     *
     * Normalizes line endings, executes regex pattern matching to locate directives,
     * and returns array of FragmentElement instances with computed offsets. Returns
     * empty array when fewer than two directives found (minimum for valid fragment).
     *
     * @param string $content Blade template content to parse
     *
     * @return CloseFragmentElement[]|OpenFragmentElement[] Array of fragment elements in order
     */
    public function parse(string $content): array
    {
        $content = $this->normalizeLineEndings($content);

        return $this->prepareNodeList($content);
    }

    /**
     * Build fragment element list from regex matches
     *
     * Constructs regex pattern matching opening directives with captured fragment names
     * and closing directives without captures. Executes pattern against content with
     * offset capture enabled, then maps matches to FragmentElement instances with
     * multibyte-adjusted offsets.
     *
     * Filters escaped directives (@@) via negative lookbehind in regex pattern. Converts
     * byte offsets to multibyte character positions using mb_strpos for Unicode support.
     * Returns empty array when insufficient matches found for valid fragment pair.
     *
     * @param string $content Normalized template content
     *
     * @return array<OpenFragmentElement|CloseFragmentElement> Fragment elements with offsets
     */
    private function prepareNodeList(string $content): array
    {
        $re = sprintf('/(?<!@)@%s[ \t]*\([\'"](.+?)[\'"]\)|@%s/', $this->openDirective, $this->closeDirective);

        preg_match_all($re, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if (count($matches) < 2) {
            return [];
        }

        $lastOffset = 0;

        /** @var array<int, OpenFragmentElement|CloseFragmentElement|null> $nodes */
        $nodes = array_map(function (array $match) use ($content, &$lastOffset) {
            $offset = $match[0][1];

            if ($offset !== 0) {
                $offset = mb_strpos($content, $match[0][0], $lastOffset + 1);
            }

            if ($offset === false) {
                $offset = $match[0][1];
            }

            $lastOffset = $offset + 1;

            if (str_starts_with($match[0][0], sprintf('@%s', $this->openDirective))) {
                $openElement = new OpenFragmentElement;
                $openElement->name = $match[1][0];
                $openElement->startOffset = $offset;
                $openElement->endOffset = $offset + mb_strlen($match[0][0]);

                return $openElement;
            }

            if (str_starts_with($match[0][0], sprintf('@%s', $this->closeDirective))) {
                $closeElement = new CloseFragmentElement;
                $closeElement->startOffset = $offset;
                $closeElement->endOffset = $offset + mb_strlen($match[0][0]);

                return $closeElement;
            }

            return null;
        }, $matches);

        return array_filter($nodes);
    }

    /**
     * Normalize line endings to Unix format
     *
     * Converts Windows (CRLF) and legacy Mac (CR) line endings to Unix (LF) format
     * for consistent parsing behavior across platforms. Ensures regex patterns match
     * correctly regardless of template file origin.
     *
     * @param string $content Raw template content
     *
     * @return string Content with normalized line endings
     */
    private function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}
