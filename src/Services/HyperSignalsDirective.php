<?php

namespace Dancycodes\Hyper\Services;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Signals Blade Directive Compiler
 *
 * Compiles @signals Blade directive expressions into data-signals HTML attributes for
 * reactive signal initialization. Parses variable references, spread operators, and
 * Laravel compact() calls from Blade templates, rewriting them into array expressions
 * that preserve literal variable names without transformation.
 *
 * Supports three signal types based on underscore positioning in variable names:
 * Regular signals use standard names, client-only signals prefix with underscore,
 * and locked signals suffix with underscore for server-side tampering protection.
 *
 * Handles variable destructuring through spread operators, allowing arrays and objects
 * to be expanded into individual signals. Integrates with Laravel compact() function
 * for convenient multi-variable signal initialization from existing template variables.
 *
 * Generated HTML attributes are JSON-encoded with proper escaping for safe embedding
 * in Blade templates. Locked signals are automatically stored in encrypted session
 * storage for validation on subsequent requests.
 *
 * @see \Dancycodes\Hyper\Http\HyperSignal
 * @see \Dancycodes\Hyper\HyperServiceProvider::registerBladeDirectives()
 */
class HyperSignalsDirective
{
    /**
     * Parse and rewrite Blade directive expression for signal initialization
     *
     * Analyzes expression components separated by commas, identifying variable references,
     * spread operators, and compact() calls. Rewrites each component into array syntax
     * preserving literal variable names as both keys and values.
     *
     * Variable pattern matching uses regex to extract complete variable names including
     * underscores for signal type identification. Spread operations are marked with
     * internal __SPREAD__ key for later array expansion in render method.
     *
     * @param string $expression Blade directive expression to parse and rewrite
     *
     * @return string Rewritten expression as comma-separated array definitions
     */
    public function parseAndRewriteExpression(string $expression): string
    {
        if (empty(trim($expression))) {
            return '';
        }

        $parts = $this->splitExpressionParts($expression);
        $rewrittenParts = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if (preg_match('/^compact\s*\(/', $part)) {
                $rewrittenParts[] = $part;
            } elseif (preg_match('/^\.\.\.?\$([a-zA-Z_][a-zA-Z0-9_]*)$/', $part, $matches)) {
                $fullVarName = $matches[1];
                $rewrittenParts[] = "['__SPREAD__' => \${$fullVarName}]";
            } elseif (preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)$/', $part, $matches)) {
                $fullVarName = $matches[1];
                $rewrittenParts[] = "['{$fullVarName}' => \${$fullVarName}]";
            } else {
                $rewrittenParts[] = $part;
            }
        }

        return implode(', ', $rewrittenParts);
    }

    /**
     * Render signals data-attribute with locked signal storage integration
     *
     * Processes variadic array arguments representing signal data, handles spread
     * marker expansion by converting spread values to signal format, merges all
     * signal arrays including compact() results, stores locked signals for validation,
     * and generates final data-signals HTML attribute with JSON-encoded values.
     *
     * @param mixed ...$arrays Signal arrays from parsed Blade expression
     *
     * @return string HTML data-signals attribute with JSON-encoded signal data
     */
    public function render(...$arrays): string
    {
        if (empty($arrays)) {
            return 'data-signals=\'{}\'';
        }

        $signals = [];

        foreach ($arrays as $array) {
            if (!is_array($array)) {
                continue;
            }

            if (isset($array['__SPREAD__'])) {
                $spreadData = $this->convertToSignal($array['__SPREAD__']);
                if (is_array($spreadData)) {
                    $signals = array_merge($signals, $spreadData);
                }
            } else {
                $signals = array_merge($signals, $array);
            }
        }

        $this->storeLockedSignalsIfNeeded($signals);

        return $this->generateSignalsAttribute($signals);
    }

    /**
     * Store locked signals to encrypted session for tampering validation
     *
     * Filters signal array for keys ending with underscore suffix indicating locked
     * signals, delegates storage to HyperSignal instance if locked signals exist.
     * HyperSignal handles first call versus subsequent call logic for session storage.
     *
     * @param array<string, mixed> $signals Signal data to check for locked signals
     */
    protected function storeLockedSignalsIfNeeded(array $signals): void
    {
        $lockedSignals = array_filter($signals, function ($value, $key) {
            return str_ends_with((string) $key, '_');
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($lockedSignals)) {
            /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
            $hyperSignal = signals();
            $hyperSignal->storeLockedSignals($signals);
        }
    }

    /**
     * Split expression into parts respecting nested brackets and string boundaries
     *
     * Parses expression character by character, tracking bracket nesting depth and
     * string literal boundaries to correctly identify comma separators between top-level
     * expression parts. Handles escaped quotes within strings, prevents splitting on
     * commas inside nested array definitions or function arguments.
     *
     * @param string $expression Blade directive expression to split
     *
     * @return array<int, string> Expression parts as individual strings
     */
    protected function splitExpressionParts(string $expression): array
    {
        $parts = [];
        $current = '';
        $bracketDepth = 0;
        $inString = false;
        $stringChar = null;

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            $prev = $i > 0 ? $expression[$i - 1] : '';

            if (($char === '"' || $char === "'") && $prev !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
            }

            if (!$inString) {
                if ($char === '[') {
                    $bracketDepth++;
                } elseif ($char === ']') {
                    $bracketDepth--;
                } elseif ($char === ',' && $bracketDepth === 0) {
                    $parts[] = trim($current);
                    $current = '';

                    continue;
                }
            }

            $current .= $char;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Convert data to signal format using Laravel serialization
     *
     * Transforms various data types to signal-compatible format using Laravel's built-in
     * serialization interfaces. Handles Arrayable objects by calling toArray(), handles
     * JsonSerializable objects by calling jsonSerialize(), passes arrays and scalar values
     * through unchanged.
     *
     * @param mixed $data Data to convert to signal format
     *
     * @return mixed Converted signal data
     */
    public function convertToSignal(mixed $data): mixed
    {
        return match (true) {
            $data instanceof Arrayable => $data->toArray(),
            $data instanceof JsonSerializable => $data->jsonSerialize(),
            is_array($data) => $data,
            default => $data,
        };
    }

    /**
     * Convert batch of signals to signal format for HyperResponse integration
     *
     * Iterates through signal array applying convertToSignal() method to each value,
     * preserving signal keys while transforming values to compatible format. Used by
     * HyperResponse for signal updates.
     *
     * @param array<string, mixed> $signals Signal key-value pairs to convert
     *
     * @return array<string, mixed> Converted signal batch with same keys
     */
    public function convertSignalBatch(array $signals): array
    {
        $convertedSignals = [];

        foreach ($signals as $key => $value) {
            $convertedSignals[$key] = $this->convertToSignal($value);
        }

        return $convertedSignals;
    }

    /**
     * Generate data-signals HTML attribute with JSON-encoded signal data
     *
     * Converts all signal values to compatible format using convertToSignal(), encodes
     * resulting array as JSON with proper HTML escaping flags, and wraps in data-signals
     * attribute syntax with single quotes. Returns empty object JSON for empty signal array.
     *
     * @param array<string, mixed> $signals Signal data to encode
     *
     * @return string HTML attribute string with JSON-encoded signals
     */
    protected function generateSignalsAttribute(array $signals): string
    {
        if (empty($signals)) {
            return 'data-signals=\'{}\'';
        }

        $convertedSignals = [];

        foreach ($signals as $key => $value) {
            $convertedSignals[$key] = $this->convertToSignal($value);
        }

        $json = json_encode(
            $convertedSignals,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );

        return "data-signals='{$json}'";
    }
}
