<?php

namespace Dancycodes\Hyper\Html\Concerns\Visual;

use Closure;
use Dancycodes\Hyper\Html\Elements\Visual\Icon;

/**
 * HasIcons Trait - Multi-Position Icon Support
 *
 * Provides comprehensive icon support for HTML elements with flexible positioning.
 * Supports multiple icons per position, fluent API, and batch operations.
 *
 * Positioning Options:
 * - **top**: Icons above content (block layout)
 * - **left**: Icons before content (inline layout) - Default for prefix
 * - **bottom**: Icons below content (block layout)
 * - **right**: Icons after content (inline layout) - Default for suffix
 * - **prefix**: Alias for left (convenience)
 * - **suffix**: Alias for right (convenience)
 *
 * Features:
 * - Multiple icons per position
 * - Fluent API with method chaining
 * - Array-based batch operations
 * - Closure support for dynamic icons
 * - Automatic Icon instance creation from strings
 *
 * Single Icon Usage:
 * ```php
 * Html::button()
 *     ->text('Save')
 *     ->leftIcon('heroicon-s-check');
 *
 * Html::button()
 *     ->text('Back')
 *     ->prefixIcon('heroicon-o-arrow-left');
 * ```
 *
 * Multiple Icons Usage:
 * ```php
 * Html::button()
 *     ->text('Action')
 *     ->leftIcon('heroicon-s-arrow-left')
 *     ->rightIcon('heroicon-s-chevron-down')
 *     ->rightIcon('heroicon-s-star');  // Multiple on same side!
 * ```
 *
 * Array-Based Batch Usage:
 * ```php
 * Html::button()
 *     ->icons([
 *         ['name' => 'heroicon-s-plus', 'position' => 'left'],
 *         ['name' => 'heroicon-s-check', 'position' => 'right'],
 *         ['name' => 'heroicon-s-star', 'position' => 'right'],
 *     ])
 *     ->text('Complex Button');
 * ```
 *
 * Custom Icon Instances:
 * ```php
 * Html::button()
 *     ->leftIcon(
 *         Icon::make('home')->lg()->class('text-blue-500')
 *     )
 *     ->text('Home');
 * ```
 *
 * Dynamic Icons with Closures:
 * ```php
 * Html::button()
 *     ->leftIcon(function() {
 *         return auth()->check() ? 'heroicon-s-user' : 'heroicon-o-user';
 *     })
 *     ->text('Profile');
 * ```
 */
trait HasIcons
{
    /**
     * Icon storage by position
     *
     * @var array<string, array<Icon>>
     */
    protected array $icons = [
        'top' => [],
        'left' => [],
        'bottom' => [],
        'right' => [],
    ];

    /**
     * Add icon to top position
     *
     * Icons in top position are rendered above the element's main content.
     * Useful for headers, titles, or block-level icon displays.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function topIcon(string|Icon|array|Closure $icon): static
    {
        $this->addIconToPosition('top', $icon);

        return $this;
    }

    /**
     * Add icon to left position (before content)
     *
     * Icons in left position are rendered before the element's main content.
     * This is the most common position for icons in buttons and links.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function leftIcon(string|Icon|array|Closure $icon): static
    {
        $this->addIconToPosition('left', $icon);

        return $this;
    }

    /**
     * Add icon to bottom position
     *
     * Icons in bottom position are rendered below the element's main content.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function bottomIcon(string|Icon|array|Closure $icon): static
    {
        $this->addIconToPosition('bottom', $icon);

        return $this;
    }

    /**
     * Add icon to right position (after content)
     *
     * Icons in right position are rendered after the element's main content.
     * Common for dropdown indicators, external link indicators, etc.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function rightIcon(string|Icon|array|Closure $icon): static
    {
        $this->addIconToPosition('right', $icon);

        return $this;
    }

    /**
     * Add prefix icon (alias for leftIcon)
     *
     * Convenience method that maps to leftIcon().
     * Follows common UI framework naming conventions.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function prefixIcon(string|Icon|array|Closure $icon): static
    {
        return $this->leftIcon($icon);
    }

    /**
     * Add suffix icon (alias for rightIcon)
     *
     * Convenience method that maps to rightIcon().
     * Follows common UI framework naming conventions.
     *
     * @param string|Icon|array|Closure $icon Icon name, Icon instance, array of icons, or closure
     */
    public function suffixIcon(string|Icon|array|Closure $icon): static
    {
        return $this->rightIcon($icon);
    }

    /**
     * Generic icon method with named parameters
     *
     * Convenience method for adding icons with full control over all properties.
     * Defaults to 'left' position if not specified.
     *
     * Examples:
     * ```php
     * // Simple usage (defaults to left position)
     * ->icon('heroicon-o-pencil')
     *
     * // With position
     * ->icon('heroicon-o-pencil', position: 'right')
     *
     * // With size
     * ->icon('heroicon-o-pencil', size: 'lg')
     *
     * // With custom provider
     * ->icon('home', provider: 'feathericons', position: 'left', size: 'md')
     *
     * // Full control
     * ->icon('check', provider: 'heroicons', position: 'right', size: 'xl', variant: 'solid')
     * ```
     *
     * @param string $name Icon name
     * @param string|null $provider Icon provider (e.g., 'heroicons', 'feathericons')
     * @param string $position Position (top, left, bottom, right) - defaults to 'left'
     * @param string|null $size Size preset (xs, sm, md, lg, xl, xxl, xxxl) or custom classes
     * @param string|null $variant Variant (solid, outline, mini for Heroicons)
     * @param string|null $class Additional CSS classes
     */
    public function icon(
        string $name,
        ?string $provider = null,
        string $position = 'left',
        ?string $size = null,
        ?string $variant = null,
        ?string $class = null
    ): static {
        // Create Icon instance
        $icon = Icon::make($name, $provider);

        // Apply variant if specified
        if ($variant !== null) {
            $icon->variant($variant);
        }

        // Apply size if specified
        if ($size !== null) {
            // Check if it's a preset or custom classes
            $presets = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'];

            if (in_array($size, $presets)) {
                $icon->{$size}(); // Call preset method
            } else {
                $icon->size($size); // Custom size classes
            }
        }

        // Apply additional classes if specified
        if ($class !== null) {
            $icon->class($class);
        }

        // Add to specified position
        $this->addIconToPosition($position, $icon);

        return $this;
    }

    /**
     * Add multiple icons at once (batch operation)
     *
     * Accepts an array of icon definitions with position information.
     * Each icon can be:
     * - Array with 'name' and 'position' keys
     * - Array with 'icon' and 'position' keys (for Icon instances)
     * - Simple string (defaults to 'left' position)
     *
     * Examples:
     * ```php
     * ->icons([
     *     ['name' => 'heroicon-s-home', 'position' => 'left'],
     *     ['name' => 'heroicon-s-star', 'position' => 'right'],
     * ])
     *
     * ->icons([
     *     ['icon' => Icon::make('home')->lg(), 'position' => 'left'],
     *     ['icon' => Icon::make('star')->sm(), 'position' => 'right'],
     * ])
     * ```
     *
     * @param array $icons Array of icon definitions
     */
    public function icons(array $icons): static
    {
        foreach ($icons as $iconData) {
            // Handle different array formats
            if (is_array($iconData)) {
                $position = $iconData['position'] ?? 'left';
                $icon = $iconData['icon'] ?? $iconData['name'] ?? $iconData[0] ?? null;

                if ($icon !== null) {
                    $this->addIconToPosition($position, $icon);
                }
            } else {
                // Simple value - default to left position
                $this->addIconToPosition('left', $iconData);
            }
        }

        return $this;
    }

    /**
     * Check if element has any icons
     */
    public function hasIcons(): bool
    {
        foreach ($this->icons as $position => $icons) {
            if (!empty($icons)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if element has icons in a specific position
     *
     * @param string $position Position to check (top, left, bottom, right)
     */
    public function hasIconsInPosition(string $position): bool
    {
        return !empty($this->icons[$position] ?? []);
    }

    /**
     * Get all icons for a specific position
     *
     * @param string $position Position (top, left, bottom, right)
     *
     * @return array<Icon>
     */
    public function getIconsForPosition(string $position): array
    {
        return $this->icons[$position] ?? [];
    }

    /**
     * Clear all icons
     *
     * @return $this
     */
    public function clearIcons(): static
    {
        $this->icons = [
            'top' => [],
            'left' => [],
            'bottom' => [],
            'right' => [],
        ];

        return $this;
    }

    /**
     * Clear icons from a specific position
     *
     * @param string $position Position to clear (top, left, bottom, right)
     *
     * @return $this
     */
    public function clearIconsInPosition(string $position): static
    {
        if (isset($this->icons[$position])) {
            $this->icons[$position] = [];
        }

        return $this;
    }

    /**
     * Add icon to specific position (internal method)
     *
     * Handles type conversion and validation:
     * - Closures are evaluated
     * - Strings are converted to Icon instances
     * - Arrays are processed recursively
     * - Icon instances are added directly
     *
     * @param string $position Position (top, left, bottom, right)
     * @param string|Icon|array|Closure $icon Icon to add
     */
    protected function addIconToPosition(string $position, string|Icon|array|Closure $icon): void
    {
        // Validate position
        if (!isset($this->icons[$position])) {
            throw new \InvalidArgumentException(
                "Invalid icon position '{$position}'. Valid positions: top, left, bottom, right"
            );
        }

        // Evaluate closure
        if ($icon instanceof Closure) {
            $icon = $this->evaluate($icon);
        }

        // Handle arrays (recursive)
        if (is_array($icon)) {
            foreach ($icon as $i) {
                $this->addIconToPosition($position, $i);
            }

            return;
        }

        // Skip null values
        if ($icon === null) {
            return;
        }

        // Convert string to Icon instance
        if (is_string($icon)) {
            $icon = Icon::make($icon);
        }

        // Validate Icon instance
        if (!$icon instanceof Icon) {
            throw new \InvalidArgumentException(
                'Icon must be string, Icon instance, array, Closure, or null. Got: ' . get_debug_type($icon)
            );
        }

        // Add to position array
        $this->icons[$position][] = $icon;
    }

    /**
     * Render all icons for a specific position
     *
     * @param string $position Position to render (top, left, bottom, right)
     *
     * @return string HTML string of rendered icons
     */
    protected function renderIconPosition(string $position): string
    {
        if (empty($this->icons[$position])) {
            return '';
        }

        $html = '';

        foreach ($this->icons[$position] as $icon) {
            $html .= (string) $icon;
        }

        return $html;
    }

    /**
     * Inject icons into element content (called during rendering)
     *
     * This method should be called from the element's toHtml() method
     * to inject icons at the appropriate positions.
     *
     * Order of injection:
     * 1. Top icons (prepended to content)
     * 2. Left icons (prepended to content, after top)
     * 3. Content (element's main content)
     * 4. Right icons (appended to content)
     * 5. Bottom icons (appended to content, after right)
     */
    protected function injectIcons(): void
    {
        // Prepend top icons (in order)
        if ($this->hasIconsInPosition('top')) {
            foreach ($this->icons['top'] as $icon) {
                $this->prependContent($icon);
            }
        }

        // Prepend left icons (in reverse order so first call appears leftmost)
        if ($this->hasIconsInPosition('left')) {
            foreach (array_reverse($this->icons['left']) as $icon) {
                $this->prependContent($icon);
            }
        }

        // Append right icons (in order)
        if ($this->hasIconsInPosition('right')) {
            foreach ($this->icons['right'] as $icon) {
                $this->appendContent($icon);
            }
        }

        // Append bottom icons (in order)
        if ($this->hasIconsInPosition('bottom')) {
            foreach ($this->icons['bottom'] as $icon) {
                $this->appendContent($icon);
            }
        }
    }

    /**
     * Prepend content to element (must be implemented by using class)
     *
     * @param mixed $item Content to prepend
     */
    abstract protected function prependContent(mixed $item): void;

    /**
     * Append content to element (must be implemented by using class)
     *
     * @param mixed $item Content to append
     */
    abstract protected function appendContent(mixed $item): void;

    /**
     * Evaluate closure (must be available from using class)
     *
     * @param mixed $value Value to evaluate (if closure)
     *
     * @return mixed Evaluated result
     */
    abstract protected function evaluate(mixed $value): mixed;
}
