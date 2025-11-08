<?php

namespace Dancycodes\Hyper\Html\Elements\Base;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Assets\ManagesAssets;
use Dancycodes\Hyper\Html\Contracts\Structure\HasChildren;

/**
 * Container Element Class - HTML Elements with Nested Child Elements
 *
 * Extends TextElement to add support for nested child elements. ContainerElement is used for
 * HTML elements that contain other elements, text nodes, or complex structures (like <div>,
 * <section>, <ul>, <form>, etc.).
 *
 * Key Features:
 * - Nested child element support via content() method
 * - Circular reference detection (prevents infinite loops)
 * - Recursion depth limits (prevents stack overflow)
 * - Automatic array flattening for nested structures
 * - Closure evaluation with Laravel dependency injection
 * - Support for mixed content (text + elements + raw HTML)
 *
 * Content Rendering Order:
 * When multiple content types are present, they render in this order:
 * 1. Text content (from parent TextElement's text() method)
 * 2. Child elements/strings (from content(), child(), children() methods)
 * 3. Raw HTML (from html() method)
 *
 * Basic Usage:
 * ```php
 * use Dancycodes\Hyper\Html\Html;
 *
 * // Simple nested structure
 * echo Html::div()->content(
 *     Html::h1()->text('Title'),
 *     Html::p()->text('Paragraph')
 * );
 *
 * // Multiple children via array
 * echo Html::ul()->children([
 *     Html::li()->text('Item 1'),
 *     Html::li()->text('Item 2'),
 *     Html::li()->text('Item 3'),
 * ]);
 *
 * // Single child (convenience method)
 * echo Html::button()->child(
 *     Html::span()->class('icon')->text('→')
 * );
 *
 * // Mixed content
 * echo Html::div()
 *     ->text('Prefix text ')
 *     ->content(
 *         Html::strong()->text('bold'),
 *         ' middle text ',
 *         Html::em()->text('italic')
 *     )
 *     ->html(' <span>raw html</span>');
 * ```
 *
 * Advanced Patterns:
 * ```php
 * // Dynamic children via closures
 * Html::div()->content(function() {
 *     return auth()->check()
 *         ? Html::p()->text('Welcome back!')
 *         : Html::p()->text('Please log in');
 * });
 *
 * // Conditional rendering with arrays
 * Html::div()->content(
 *     Html::h1()->text('Title'),
 *     auth()->check() ? Html::p()->text('Logged in') : null,
 *     ['nested', 'array', ['of', 'items']] // Automatically flattened
 * );
 *
 * // Complex component patterns
 * Html::div()->class('card')->content(
 *     Html::div()->class('card-header')->content(
 *         Html::h2()->class('card-title')->text($title)
 *     ),
 *     Html::div()->class('card-body')->content(
 *         Html::p()->text($body)
 *     ),
 *     Html::div()->class('card-footer')->content(
 *         Html::button()->class('btn')->text('Action')
 *     )
 * );
 * ```
 *
 * Security Features:
 *
 * 1. **Circular Reference Detection**:
 *    Prevents elements from containing themselves (directly or indirectly).
 *    Throws RuntimeException if circular reference is detected.
 *    ```php
 *    $div = Html::div();
 *    $div->content($div); // RuntimeException: Circular reference detected
 *    ```
 *
 * 2. **Recursion Depth Limits**:
 *    Maximum nesting depth is 100 levels to prevent stack overflow.
 *    Applies to both element nesting and array flattening.
 *    ```php
 *    // Safe: 10 levels of nesting
 *    Html::div()->content(
 *        Html::div()->content(
 *            Html::div()->content(
 *                // ... up to 10 levels deep
 *            )
 *        )
 *    );
 *
 *    // RuntimeException: 101+ levels exceed limit
 *    ```
 *
 * 3. **XSS Protection**:
 *    All string children are automatically escaped.
 *    Element children use their own __toString() for error-safe rendering.
 *
 * Performance Considerations:
 * - content() accepts variadic arguments for optimal memory usage
 * - Circular reference tracking uses object hashing (O(1) lookup)
 * - Array flattening is depth-limited and optimized
 * - Closures are evaluated lazily during render time
 *
 * Closure Parameters:
 * Closures passed to content() can receive these injected parameters:
 * - $element - This element instance
 * - $container - This container element (same as $element)
 * - $children - Current children array
 * - Any Laravel service via dependency injection
 *
 * Example with DI:
 * ```php
 * Html::div()->content(function($container, $children) {
 *     $count = count($children);
 *     return Html::p()->text("Currently has {$count} children");
 * });
 * ```
 *
 * Common Patterns:
 * ```php
 * // Navigation menu
 * Html::nav()->children([
 *     Html::a()->href('/')->text('Home'),
 *     Html::a()->href('/about')->text('About'),
 *     Html::a()->href('/contact')->text('Contact'),
 * ]);
 *
 * // Form with inputs
 * Html::form()->content(
 *     Html::div()->content(
 *         Html::label()->for('email')->text('Email'),
 *         Html::input()->type('email')->id('email')
 *     ),
 *     Html::button()->type('submit')->text('Submit')
 * );
 *
 * // Table structure
 * Html::table()->content(
 *     Html::thead()->child(
 *         Html::tr()->children([
 *             Html::th()->text('Name'),
 *             Html::th()->text('Email'),
 *         ])
 *     ),
 *     Html::tbody()->children(
 *         collect($users)->map(fn($user) =>
 *             Html::tr()->children([
 *                 Html::td()->text($user->name),
 *                 Html::td()->text($user->email),
 *             ])
 *         )->all()
 *     )
 * );
 * ```
 *
 * @see \Dancycodes\Hyper\Html\Elements\Base\Element
 * @see \Dancycodes\Hyper\Html\Elements\Base\TextElement
 * @see \Dancycodes\Hyper\Html\Html
 */
abstract class ContainerElement extends TextElement implements HasChildren
{
    use ManagesAssets;

    protected array $children = [];
    protected ?string $rawHtml = null;

    /**
     * Tracks element object hashes currently being added to detect circular references
     *
     * @var array<string, true>
     */
    protected static array $additionStack = [];

    /**
     * Add content to the element (variadic - accepts strings, elements, arrays, closures)
     *
     * Accepts closures for dynamic content generation. Closures are evaluated
     * with dependency injection and their results are processed recursively.
     *
     * Handles:
     * - Closures: Evaluated with DI, result processed recursively
     * - Strings: Escaped and added as text nodes
     * - Element instances: Added as child elements (with circular reference detection)
     * - Arrays: Flattened and recursively processed
     * - Null: Skipped (no-op)
     *
     * Recursion depth is protected to prevent stack overflow from deeply nested
     * or circular array structures. Maximum depth is 100 levels by default.
     *
     * Circular reference detection prevents elements from containing themselves,
     * either directly or through a chain of parent-child relationships.
     *
     * @param string|Element|array|Closure|null ...$items Content items or closures returning content
     *
     * @throws \RuntimeException If maximum recursion depth is exceeded or circular reference detected
     */
    public function content(string|Element|array|Closure|null ...$items): static
    {
        // Track this container during content processing
        $hash = spl_object_hash($this);
        self::$additionStack[$hash] = true;

        try {
            return $this->processContent($items, 0, 100);
        } finally {
            // Clean up stack after processing all content
            unset(self::$additionStack[$hash]);
        }
    }

    /**
     * Internal recursive content processor with depth tracking
     *
     * @param array $items Content items to process
     * @param int $depth Current recursion depth
     * @param int $maxDepth Maximum allowed recursion depth
     *
     * @throws \RuntimeException If maximum recursion depth is exceeded
     */
    protected function processContent(array $items, int $depth, int $maxDepth): static
    {
        // Check recursion depth limit
        if ($depth > $maxDepth) {
            throw new \RuntimeException(
                sprintf(
                    'Maximum content nesting depth of %d exceeded. Possible circular reference or overly deep structure.',
                    $maxDepth
                )
            );
        }

        foreach ($items as $item) {
            // Evaluate if closure
            if ($item instanceof Closure) {
                $item = $this->evaluate($item);
            }

            // Handle evaluated result by type
            if ($item === null) {
                // Skip null values (no-op)
                continue;
            } elseif ($item instanceof Closure) {
                // Recursive closure - evaluate again
                $item = $this->evaluate($item);
            }

            // Now process the final resolved value
            if ($item instanceof Element) {
                // Check for circular reference
                $itemHash = spl_object_hash($item);
                if (isset(self::$additionStack[$itemHash])) {
                    throw new \RuntimeException(
                        'Circular reference detected: element cannot contain itself. ' .
                        'Element of type ' . get_class($item) . ' is already being added to the tree.'
                    );
                }

                // Element instance - add directly
                $this->children[] = $item;
            } elseif (is_string($item)) {
                // String - will be escaped during rendering
                $this->children[] = $item;
            } elseif (is_array($item)) {
                // Array - recursively process each element with incremented depth
                $this->processContent($item, $depth + 1, $maxDepth);
            } elseif ($item === null) {
                // Null after recursive evaluation - skip
                continue;
            } else {
                // Invalid type
                throw new \InvalidArgumentException(
                    'Content must be string, Element, array, Closure, or null. Got: ' . get_debug_type($item)
                );
            }
        }

        return $this;
    }

    /**
     * Add a single child element (convenience method)
     *
     * This is a convenience wrapper around content() for adding a single child.
     * Provides a more intuitive API matching popular frameworks like Filament.
     *
     * @param string|Element|Closure|null $child Single child element, string, or closure
     */
    public function child(string|Element|Closure|null $child): static
    {
        return $this->content($child);
    }

    /**
     * Add multiple children from an array (convenience method)
     *
     * This is a convenience wrapper around content() for adding multiple children
     * from an array. Provides a more intuitive API matching popular frameworks.
     *
     * Example:
     * ```php
     * Html::ul()->children([
     *     Html::li('Item 1'),
     *     Html::li('Item 2'),
     *     Html::li('Item 3'),
     * ])
     * ```
     *
     * @param array $children Array of child elements, strings, or closures
     */
    public function children(array $children): static
    {
        return $this->content(...$children);
    }

    /**
     * Add raw HTML content (dangerous - no escaping)
     *
     * Accepts closures for dynamic HTML generation. Closures are evaluated
     * with dependency injection before setting the content.
     *
     * Use this method with caution. Content is NOT escaped.
     *
     * @param string|Closure $html Raw HTML content or closure returning HTML
     */
    public function html(string|Closure $html): static
    {
        // 1. Evaluate the HTML if it's a closure
        $html = $this->evaluate($html);

        // 2. Store raw HTML (will be included unescaped during rendering)
        $this->rawHtml = $html;

        // 3. Return $this for method chaining
        return $this;
    }

    /**
     * Render child element to string
     *
     * Uses __toString() for Element instances to ensure error handling is applied.
     */
    protected function renderChild(mixed $child): string
    {
        if ($child instanceof Element) {
            // Element instance - use __toString() for error handling
            return (string) $child;
        } elseif (is_string($child)) {
            // String - escape for XSS protection
            return htmlspecialchars($child, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        } else {
            // Invalid type - log warning and return empty string
            logger()->warning('Invalid child type in ContainerElement', [
                'type' => get_debug_type($child),
                'class' => static::class,
            ]);

            return '';
        }
    }

    /**
     * Render container element to HTML
     */
    public function toHtml(): string
    {
        $attributes = $this->renderAttributes();

        // Build content from multiple sources
        $content = '';

        // 1. Text content from parent TextElement (if set)
        if ($this->textContent !== null) {
            $content .= htmlspecialchars($this->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        // 2. Children elements/strings
        foreach ($this->children as $child) {
            $content .= $this->renderChild($child);
        }

        // 3. Raw HTML (dangerous but sometimes necessary)
        if ($this->rawHtml !== null) {
            $content .= $this->rawHtml;
        }

        return "<{$this->tag}{$attributes}>{$content}</{$this->tag}>";
    }

    /**
     * Resolve default closure dependencies by parameter name
     *
     * Provides container-specific context to closures in addition to
     * the base element context.
     *
     * Available named parameters (in addition to parent's):
     * - $container: This container element instance
     * - $children: Array of child elements
     *
     * @param string $parameterName The closure parameter name
     *
     * @return array{0: mixed}|array Array with value at index 0, or empty array
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'container' => [$this],
            'children' => [$this->children],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
