<?php

namespace Dancycodes\Hyper\Html\Elements\Base;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Core\HasAriaAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Core\HasDataAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Core\HasDatastarAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Core\HasGlobalAttributes;
use Dancycodes\Hyper\Html\Concerns\Core\ConditionalRendering;
use Dancycodes\Hyper\Html\Concerns\Core\EvaluatesClosures;
use Dancycodes\Hyper\Html\Contracts\Rendering\Renderable;
use Stringable;

/**
 * Base HTML Element Class - Foundation for All HTML Elements
 *
 * Provides the core functionality for creating type-safe, XSS-protected HTML elements
 * programmatically. This abstract base class handles attributes, classes, tag validation,
 * and rendering for all HTML element types.
 *
 * Architecture & Design:
 *
 * Element serves as the foundation of a 3-tier inheritance hierarchy:
 * 1. Element (this class) - Core attributes, classes, rendering
 * 2. TextElement extends Element - Adds text() and html() methods for simple content
 * 3. ContainerElement extends TextElement - Adds content() for nested child elements
 * 4. VoidElement extends Element - Self-closing tags (br, hr, img, input, etc.)
 *
 * Key Features:
 * - Automatic XSS protection via htmlspecialchars() with secure flags
 * - Support for all HTML5 global attributes (id, class, style, data-*, aria-*, etc.)
 * - Full Datastar integration (data-bind, data-on:*, data-text, etc.)
 * - Fluent method chaining for readable, expressive code
 * - Laravel dependency injection support in closures
 * - Tag name validation per HTML5 specification
 * - Recursion depth limits to prevent stack overflow
 * - Performance optimizations (class merge caching, attribute batching)
 *
 * Security Features:
 * - All attribute names are escaped to prevent XSS
 * - All attribute values are escaped using ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5
 * - Tag names are validated and sanitized (only alphanumeric + hyphens allowed)
 * - Array flattening has depth limits (100 levels) to prevent stack overflow
 * - Boolean attributes follow HTML5 spec (empty string value or omitted)
 *
 * Basic Usage:
 * ```php
 * use Dancycodes\Hyper\Html\Html;
 *
 * // Create element with attributes
 * $div = Html::div()
 *     ->id('container')
 *     ->class('card shadow-lg')
 *     ->attr('data-foo', 'bar');
 *
 * // Dynamic attributes via closures
 * $button = Html::button()
 *     ->class(function() {
 *         return auth()->check() ? 'btn-primary' : 'btn-secondary';
 *     })
 *     ->disabled(fn() => !auth()->check());
 *
 * // With Datastar attributes
 * $input = Html::input()
 *     ->type('text')
 *     ->dataBind('username')
 *     ->dataOnInput('@patch("/validate")');
 * ```
 *
 * Tailwind CSS Class Merging:
 *
 * Tailwind class merging is INTENTIONALLY DISABLED (see line 195-232 for full rationale).
 * The built-in mergeTailwindClasses() method has known limitations:
 * - No support for responsive modifiers (md:, lg:, xl:)
 * - No support for state modifiers (hover:, focus:, active:)
 * - No support for arbitrary values (bg-[#fff])
 * - Incomplete handling of multi-axis spacing (p-4 px-8)
 * - No support for variant combinations (dark:md:bg-red-500)
 *
 * Current behavior: All classes are preserved in order. CSS cascade determines which wins.
 * This is predictable, safe, and allows intentional class ordering when needed.
 *
 * For proper Tailwind merging, use the tailwind-merge package or carefully curate class lists.
 *
 * Performance Optimizations:
 * - Class merge results are cached (LRU cache with 1000 entry limit)
 * - Attributes are rendered in a single pass
 * - Early returns when no attributes/classes exist
 * - Static arrays for void elements list and conflicting prefixes
 *
 * Error Handling:
 * - __toString() catches all exceptions to prevent fatal errors
 * - Errors are logged with full context (tag, class, file, line)
 * - Development mode shows detailed error messages
 * - Production mode shows generic error comments
 *
 * @see \Dancycodes\Hyper\Html\Elements\Base\TextElement
 * @see \Dancycodes\Hyper\Html\Elements\Base\ContainerElement
 * @see \Dancycodes\Hyper\Html\Elements\Base\VoidElement
 * @see \Dancycodes\Hyper\Html\Html
 * @see https://html.spec.whatwg.org/multipage/
 */
abstract class Element implements Renderable, Stringable
{
    // ARIA attributes are global to all HTML elements
    use ConditionalRendering;
    use EvaluatesClosures;
    use HasAriaAttributes;
    use HasDataAttributes;  // Standard HTML data-* attributes
    use HasDatastarAttributes;
    use HasGlobalAttributes;

    protected string $tag;
    protected array $attributes = [];
    protected array $classes = [];
    protected bool $isVoid = false;
    protected ?string $rawHtml = null;

    /**
     * Cache for class merging results (performance optimization)
     * Key: serialized classes array, Value: merged classes array
     *
     * @var array<string, array>
     */
    protected static array $classMergeCache = [];

    /**
     * HTML5 void elements that cannot have children or closing tags
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
     */
    protected const VOID_ELEMENTS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    public function __construct(string $tag)
    {
        // Validate and sanitize tag name
        $this->tag = $this->validateAndSanitizeTag($tag);
        $this->isVoid = in_array($this->tag, self::VOID_ELEMENTS);

        // Set evaluation identifier for closure parameter injection
        // Allows closures to receive the element as $element parameter
        $this->evaluationIdentifier = 'element';
    }

    /**
     * Validate and sanitize HTML tag name
     *
     * HTML5 tag names must:
     * - Start with a letter (a-z, A-Z)
     * - Contain only letters, numbers, and hyphens
     * - Not be empty
     * - Custom elements (web components) must contain a hyphen
     *
     * @param string $tag The tag name to validate
     *
     * @throws \InvalidArgumentException If tag name is invalid
     *
     * @return string The sanitized (lowercase) tag name
     *
     * @see https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     */
    protected function validateAndSanitizeTag(string $tag): string
    {
        // Check if empty
        if (empty($tag)) {
            throw new \InvalidArgumentException(
                'HTML tag name cannot be empty.'
            );
        }

        // Validate format: must start with letter, contain only letters, numbers, hyphens
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $tag)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid HTML tag name: "%s". Tag names must start with a letter and contain only letters, numbers, and hyphens.',
                    htmlspecialchars($tag, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        // Return lowercase version (HTML is case-insensitive)
        return strtolower($tag);
    }

    /**
     * Factory method for creating element instances
     *
     * @param mixed ...$args
     */
    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set an HTML attribute
     *
     * Accepts closures for dynamic attribute values. Closures are evaluated
     * with dependency injection before setting the attribute.
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value (string, int, bool, Closure, etc.)
     */
    public function attr(string $name, mixed $value): static
    {
        // 1. Evaluate the value if it's a closure
        $value = $this->evaluate($value);

        // 2. Handle boolean attributes (e.g., required, disabled, checked)
        if (is_bool($value)) {
            if ($value === true) {
                // Set to empty string for boolean attributes (HTML5 spec)
                $this->attributes[$name] = '';
            } else {
                // Remove the attribute if false
                unset($this->attributes[$name]);
            }

            return $this;
        }

        // 3. Store the attribute (will be escaped during rendering)
        $this->attributes[$name] = $value;

        // 4. Return $this for method chaining
        return $this;
    }

    /**
     * Add CSS classes
     *
     * Accepts strings, arrays, or closures that return strings/arrays.
     * Supports smart Tailwind CSS class merging (conflicting utilities are resolved).
     *
     * @param string|array|Closure ...$classes Class names or closures returning class names
     */
    public function class(string|array|Closure ...$classes): static
    {
        foreach ($classes as $class) {
            // 1. Evaluate if closure
            $class = $this->evaluate($class);

            // Skip if null or empty
            if (empty($class)) {
                continue;
            }

            // 2. Normalize to array
            if (is_string($class)) {
                // Split on whitespace for multiple classes
                $class = preg_split('/\s+/', trim($class), -1, PREG_SPLIT_NO_EMPTY);
            } elseif (!is_array($class)) {
                // Convert single values to array
                $class = [$class];
            }

            // 3. Flatten nested arrays recursively
            $class = $this->flattenArray($class);

            // 4. Add to classes array
            foreach ($class as $className) {
                if (!empty($className) && is_string($className)) {
                    $this->classes[] = $className;
                }
            }
        }

        // 5. Tailwind class merging is INTENTIONALLY DISABLED
        //
        // RATIONALE FOR KEEPING DISABLED:
        // - Preserves user intent and avoids surprising behavior changes
        // - Users who want to merge classes can use tailwind-merge package or carefully curate class lists
        // - Current implementation has known limitations that could cause bugs:
        //
        // KNOWN LIMITATIONS OF mergeTailwindClasses():
        // 1. Doesn't handle responsive modifiers (md:, lg:, xl:, 2xl:, etc.)
        //    Example: ->class('md:bg-red-500 lg:bg-blue-500')
        //    Issue: Won't recognize these as bg- conflicts because they start with md:/lg:
        //
        // 2. Doesn't handle state modifiers (hover:, focus:, active:, etc.)
        //    Example: ->class('hover:bg-red-500 focus:bg-blue-500')
        //    Issue: Won't recognize these as bg- conflicts because they start with hover:/focus:
        //
        // 3. Doesn't handle arbitrary values (bg-[#fff], w-[500px], etc.)
        //    Example: ->class('bg-[#ff0000] bg-[#0000ff]')
        //    Issue: Won't match bg- pattern exactly due to brackets
        //
        // 4. Doesn't handle multi-axis spacing conflicts correctly
        //    Example: ->class('p-4 px-8')  // px-8 should override x-axis of p-4
        //    Issue: Treats p- and px- as separate prefixes, both kept
        //
        // 5. Doesn't handle variant combinations (dark:hover:, md:focus:, etc.)
        //    Example: ->class('dark:md:bg-red-500 dark:md:bg-blue-500')
        //    Issue: Complex prefix parsing needed
        //
        // FUTURE CONSIDERATIONS:
        // - If enabling this feature is desired, consider using tailwind-merge package (battle-tested)
        // - Alternatively, implement proper variant parser to handle all Tailwind v3+ features
        // - Add feature flag in config: 'html_builder.merge_tailwind_classes' => false
        //
        // CURRENT BEHAVIOR:
        // All classes are preserved in the order they're added. CSS cascade determines which wins.
        // This is predictable, safe, and allows intentional class ordering when needed.
        //
        // $this->classes = $this->mergeTailwindClasses($this->classes);

        return $this;
    }

    /**
     * Recursively flatten arrays with depth protection
     *
     * Prevents stack overflow from deeply nested or circular array structures.
     * Maximum depth is 100 levels by default.
     *
     * @param array $array The array to flatten
     * @param int $depth Current recursion depth (internal use)
     * @param int $maxDepth Maximum allowed recursion depth
     *
     * @throws \RuntimeException If maximum recursion depth is exceeded
     *
     * @return array Flattened array
     */
    protected function flattenArray(array $array, int $depth = 0, int $maxDepth = 100): array
    {
        // Check recursion depth limit
        if ($depth > $maxDepth) {
            throw new \RuntimeException(
                sprintf(
                    'Maximum array nesting depth of %d exceeded. Possible circular reference or overly deep structure.',
                    $maxDepth
                )
            );
        }

        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                // Recursively flatten with incremented depth
                $result = array_merge($result, $this->flattenArray($item, $depth + 1, $maxDepth));
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Merge Tailwind classes, resolving conflicts
     *
     * Basic implementation: For common Tailwind utilities, last class wins.
     * More sophisticated merging can be added later.
     */
    protected function mergeTailwindClasses(array $classes): array
    {
        // Performance optimization: check cache first
        $cacheKey = implode('|', $classes);
        if (isset(self::$classMergeCache[$cacheKey])) {
            return self::$classMergeCache[$cacheKey];
        }

        $merged = [];
        $prefixes = [];

        // Common Tailwind prefixes that should conflict (static for performance)
        static $conflictingPrefixes = [
            'bg-',
            'text-',
            'border-',
            'rounded-',
            'shadow-',
            'p-',
            'px-',
            'py-',
            'pt-',
            'pr-',
            'pb-',
            'pl-',
            'm-',
            'mx-',
            'my-',
            'mt-',
            'mr-',
            'mb-',
            'ml-',
            'w-',
            'h-',
            'min-w-',
            'max-w-',
            'min-h-',
            'max-h-',
            'flex-',
            'grid-',
            'gap-',
            'space-x-',
            'space-y-',
            'opacity-',
            'font-',
            'leading-',
            'tracking-',
        ];

        foreach ($classes as $class) {
            $found = false;

            // Check if this class conflicts with a prefix
            foreach ($conflictingPrefixes as $prefix) {
                if (str_starts_with($class, $prefix)) {
                    // Store/replace with the latest class for this prefix
                    $prefixes[$prefix] = $class;
                    $found = true;
                    break;
                }
            }

            // If no conflicting prefix found, just add the class
            if (!$found) {
                $merged[] = $class;
            }
        }

        // Add all the latest prefix-based classes
        foreach ($prefixes as $class) {
            $merged[] = $class;
        }

        // Remove duplicates
        $result = array_values(array_unique($merged));

        // Cache the result for future use
        self::$classMergeCache[$cacheKey] = $result;

        // Prevent cache from growing too large (keep last 1000 entries)
        if (count(self::$classMergeCache) > 1000) {
            self::$classMergeCache = array_slice(self::$classMergeCache, -500, null, true);
        }

        return $result;
    }

    /**
     * Render element attributes to string (optimized)
     */
    protected function renderAttributes(): string
    {
        // Early return optimization: no attributes at all
        if (empty($this->classes) && empty($this->attributes)) {
            return '';
        }

        $attributes = [];

        // Add classes as a single 'class' attribute
        if (!empty($this->classes)) {
            $attributes[] = 'class="' . htmlspecialchars(implode(' ', $this->classes), ENT_QUOTES, 'UTF-8') . '"';
        }

        // Add other attributes (optimized loop)
        if (!empty($this->attributes)) {
            foreach ($this->attributes as $name => $value) {
                // Boolean attributes (empty string value) - no value needed
                if ($value === '' || $value === null) {
                    $attributes[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                } else {
                    // Regular attributes with values (escape both name and value for XSS protection)
                    // Optimization: combine escaping operations
                    $attributes[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '="' .
                        htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }

        // Optimization: use single space prefix with implode
        return ' ' . implode(' ', $attributes);
    }

    /**
     * Render the element to HTML string
     */
    abstract public function toHtml(): string;

    /**
     * Render the element with optional layout wrapper
     *
     * @param mixed $layout Blade view name for layout wrapper
     *
     * @return mixed HTML string or view response
     */
    public function render(mixed $layout = null): mixed
    {
        // Generate the HTML
        $html = $this->toHtml();

        // If no layout, return raw HTML
        if ($layout === null) {
            return $html;
        }

        // Wrap in Blade layout (pass HTML as $slot variable)
        return view($layout, ['slot' => $html]);
    }

    /**
     * Allow casting to string
     *
     * Wraps toHtml() in try-catch to prevent fatal errors.
     * PHP's __toString() cannot throw exceptions, so we catch all throwables
     * and return a safe error message instead.
     */
    public function __toString(): string
    {
        try {
            return $this->toHtml();
        } catch (\Throwable $e) {
            // Log the error for debugging
            logger()->error('Element rendering failed', [
                'tag' => $this->tag,
                'class' => static::class,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return safe fallback based on environment
            if (config('app.debug')) {
                // Development: Show detailed error
                return sprintf(
                    '<!-- Error rendering <%s> element: %s in %s:%d -->',
                    htmlspecialchars($this->tag, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars(basename($e->getFile()), ENT_QUOTES, 'UTF-8'),
                    $e->getLine()
                );
            }

            // Production: Show generic error
            return sprintf(
                '<!-- Error rendering <%s> element -->',
                htmlspecialchars($this->tag, ENT_QUOTES, 'UTF-8')
            );
        }
    }

    /**
     * Get the tag name
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * Get debug information about this element
     *
     * Returns detailed information about the element's current state,
     * useful for debugging and development.
     *
     * @return array{tag: string, class: string, attributes: array, classes: array, is_void: bool, children_count: int}
     */
    public function debug(): array
    {
        return [
            'tag' => $this->tag,
            'class' => static::class,
            'attributes' => $this->attributes,
            'classes' => $this->classes,
            'is_void' => $this->isVoid,
            'children_count' => $this instanceof ContainerElement ? count($this->children) : 0,
        ];
    }

    /**
     * Dump element information and continue execution
     *
     * Useful for debugging during element construction.
     * Returns $this for method chaining.
     */
    public function dump(): static
    {
        dump($this->debug());

        return $this;
    }

    /**
     * Dump element information and terminate execution
     *
     * Useful for quick debugging. This method will terminate script execution.
     */
    public function dd(): never
    {
        dd($this->debug());
    }

    /**
     * Resolve default closure dependencies by parameter name
     *
     * Provides element-specific context to closures. Override in subclasses
     * to provide additional context.
     *
     * Available named parameters:
     * - $element: This element instance
     * - $tag: The HTML tag name
     * - $attributes: Current attributes array
     * - $classes: Current classes array
     *
     * @param string $parameterName The closure parameter name
     *
     * @return array{0: mixed}|array Array with value at index 0, or empty array
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'element' => [$this],
            'tag' => [$this->tag],
            'attributes' => [$this->attributes],
            'classes' => [$this->classes],
            default => [],
        };
    }

    /**
     * Resolve default closure dependencies by parameter type
     *
     * Provides typed context to closures. Override in subclasses to provide
     * additional typed context.
     *
     * @param string $parameterType The fully qualified class name
     *
     * @return array{0: mixed}|array Array with value at index 0, or empty array
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        // Allow injecting this element by its class type or Element base class
        if (is_a($this, $parameterType)) {
            return [$this];
        }

        return [];
    }
}
