<?php

namespace Dancycodes\Hyper\Html\Elements\Visual;

use Dancycodes\Hyper\Html\Elements\Base\Element;
use Dancycodes\Hyper\Html\Services\IconManager;

/**
 * Icon Element - Standalone SVG Icon Component
 *
 * Renders SVG icons from registered icon providers with full attribute support,
 * sizing options, variants, and accessibility features.
 *
 * Features:
 * - Multiple provider support (Heroicons, Font Awesome, custom)
 * - Size presets (xs, sm, md, lg, xl) or custom classes
 * - Variant support (solid, outline, mini for Heroicons)
 * - Accessibility (decorative vs semantic icons)
 * - Full attribute support (class, style, data attributes, etc.)
 *
 * Basic Usage:
 * ```php
 * // Simple icon
 * Icon::make('heroicon-s-home');
 *
 * // With sizing
 * Icon::make('heroicon-o-user')->lg();
 *
 * // With custom classes
 * Icon::make('home')->class('text-blue-500 hover:text-blue-700');
 *
 * // Semantic icon (for screen readers)
 * Icon::make('heroicon-s-check')->semantic()->title('Success');
 * ```
 *
 * Provider-Specific Usage:
 * ```php
 * // Heroicons with variant
 * Icon::make('home', 'heroicons')->solid();
 * Icon::make('home', 'heroicons')->outline();
 * Icon::make('home', 'heroicons')->mini();
 *
 * // Custom provider
 * Icon::make('custom-icon', 'my-provider');
 * ```
 *
 * Accessibility:
 * - Decorative icons (default): `aria-hidden="true"` - hidden from screen readers
 * - Semantic icons: `role="img"` + title for screen reader context
 */
class Icon extends Element
{
    protected string $iconName;

    protected ?string $provider = null;

    protected ?string $variant = null;

    protected string $size = 'md';

    protected bool $semantic = false;

    protected ?string $title = null;

    /**
     * Constructor
     *
     * Icons don't have a fixed tag since they resolve to SVG from providers.
     * We pass 'svg' as a placeholder to satisfy parent constructor.
     */
    public function __construct()
    {
        parent::__construct('svg');
    }

    /**
     * Create a new Icon instance
     *
     * @param mixed ...$args Arguments: [0] = icon name, [1] = provider name (optional)
     */
    public static function make(...$args): static
    {
        $icon = new static();
        $icon->iconName = $args[0] ?? '';
        $icon->provider = $args[1] ?? null;

        return $icon;
    }

    /**
     * Set the icon provider
     *
     * @param string $provider Provider name (e.g., 'heroicons', 'fontawesome')
     */
    public function provider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set the icon variant
     *
     * @param string $variant Variant name (e.g., 'solid', 'outline', 'mini')
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Set custom size with arbitrary classes
     *
     * For sizes beyond presets, use this to set custom size classes.
     *
     * Example:
     * ```php
     * Icon::make('home')->size('h-12 w-12');
     * Icon::make('star')->size('h-24 w-24');
     * Icon::make('check')->size('size-16');
     * ```
     *
     * @param string $sizeClasses Custom size classes (e.g., 'h-12 w-12', 'size-16')
     */
    public function size(string $sizeClasses): static
    {
        $this->size = $sizeClasses;

        return $this;
    }

    /**
     * Set size to extra small (h-3 w-3 / 12px)
     */
    public function xs(): static
    {
        $this->size = 'xs';

        return $this;
    }

    /**
     * Set size to small (h-4 w-4 / 16px)
     */
    public function sm(): static
    {
        $this->size = 'sm';

        return $this;
    }

    /**
     * Set size to medium (h-5 w-5 / 20px) - default
     */
    public function md(): static
    {
        $this->size = 'md';

        return $this;
    }

    /**
     * Set size to large (h-6 w-6 / 24px)
     */
    public function lg(): static
    {
        $this->size = 'lg';

        return $this;
    }

    /**
     * Set size to extra large (h-8 w-8 / 32px)
     */
    public function xl(): static
    {
        $this->size = 'xl';

        return $this;
    }

    /**
     * Set size to 2xl (h-10 w-10 / 40px)
     */
    public function xxl(): static
    {
        $this->size = '2xl';

        return $this;
    }

    /**
     * Set size to 3xl (h-12 w-12 / 48px)
     */
    public function xxxl(): static
    {
        $this->size = '3xl';

        return $this;
    }

    /**
     * Set icon to solid variant (Heroicons)
     */
    public function solid(): static
    {
        return $this->variant('solid');
    }

    /**
     * Set icon to outline variant (Heroicons)
     */
    public function outline(): static
    {
        return $this->variant('outline');
    }

    /**
     * Set icon to mini variant (Heroicons 20px)
     */
    public function mini(): static
    {
        return $this->variant('mini');
    }

    /**
     * Mark icon as semantic (not decorative)
     *
     * Semantic icons have meaning and should be announced by screen readers.
     * Sets role="img" and optionally sets aria-label for context.
     *
     * @param bool|string $value True for semantic, or string for semantic with aria-label
     */
    public function semantic(bool|string $value = true): static
    {
        if (is_string($value)) {
            $this->semantic = true;
            $this->title = $value;
        } else {
            $this->semantic = $value;
        }

        return $this;
    }

    /**
     * Render icon to HTML
     *
     * Resolves the icon from the provider, applies size classes,
     * handles accessibility, and injects all attributes into the SVG tag.
     *
     * @return string HTML string containing the SVG icon
     *
     * @throws \InvalidArgumentException If icon cannot be resolved
     */
    public function toHtml(): string
    {
        // Resolve SVG content from IconManager
        $svg = $this->resolveIcon();

        // Apply size classes (unless custom classes already set)
        if (! $this->hasCustomSizeClasses()) {
            $this->class($this->getSizeClass());
        }

        // Handle accessibility
        $this->applyAccessibility();

        // Inject attributes into SVG tag
        return $this->injectAttributesIntoSvg($svg);
    }

    /**
     * Resolve icon from IconManager
     *
     * @return string SVG content
     *
     * @throws \InvalidArgumentException If icon cannot be resolved
     */
    protected function resolveIcon(): string
    {
        $manager = app(IconManager::class);

        return $manager->resolve(
            $this->iconName,
            $this->provider,
            $this->variant
        );
    }

    /**
     * Get size class for current size setting
     *
     * @return string Tailwind classes for icon size
     */
    protected function getSizeClass(): string
    {
        return match ($this->size) {
            'xs' => 'h-3 w-3',
            'sm' => 'h-4 w-4',
            'md' => 'h-5 w-5',
            'lg' => 'h-6 w-6',
            'xl' => 'h-8 w-8',
            '2xl' => 'h-10 w-10',
            '3xl' => 'h-12 w-12',
            // If not a preset, assume it's custom classes
            default => $this->size,
        };
    }

    /**
     * Check if custom size classes are already set
     *
     * @return bool
     */
    protected function hasCustomSizeClasses(): bool
    {
        $classString = implode(' ', $this->classes);

        // Check if any height/width classes are present
        return preg_match('/\b(h-|w-|size-)\d+/', $classString) === 1;
    }

    /**
     * Apply accessibility attributes
     *
     * - Decorative icons: aria-hidden="true" (default)
     * - Semantic icons: role="img" + <title> tag for screen readers
     */
    protected function applyAccessibility(): void
    {
        if ($this->semantic) {
            // Semantic icon - should be announced by screen readers
            $this->attr('role', 'img');

            if ($this->title !== null) {
                $this->attr('aria-label', $this->title);
            }
        } else {
            // Decorative icon - hidden from screen readers
            $this->attr('aria-hidden', 'true');
        }
    }

    /**
     * Inject attributes into SVG tag
     *
     * Takes the resolved SVG content and injects all element attributes
     * into the opening <svg> tag.
     *
     * @param string $svg Original SVG content from provider
     *
     * @return string SVG with injected attributes
     */
    protected function injectAttributesIntoSvg(string $svg): string
    {
        $attributes = $this->renderAttributes();

        // If we have a title for semantic icons, inject it into the SVG
        if ($this->semantic && $this->title !== null) {
            $titleTag = '<title>' . htmlspecialchars($this->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>';
            // Inject title right after opening <svg> tag
            $svg = preg_replace('/<svg([^>]*)>/', '<svg$1>' . $titleTag, $svg, 1);
        }

        // Inject attributes into opening <svg> tag
        return preg_replace('/<svg/', '<svg' . $attributes, $svg, 1);
    }
}
