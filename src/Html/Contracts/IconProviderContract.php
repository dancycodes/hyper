<?php

namespace Dancycodes\Hyper\Html\Contracts;

/**
 * Icon Provider Contract
 *
 * Defines the interface that all icon providers must implement.
 * Icon providers are responsible for resolving icon names to SVG content.
 *
 * Built-in Providers:
 * - HeroiconsProvider: Heroicons from blade-ui-kit/blade-heroicons package
 *
 * Custom providers can be registered via:
 * ```php
 * Html::iconProvider('my-icons', MyIconProvider::class);
 * ```
 *
 * Example Implementation:
 * ```php
 * class MyIconProvider implements IconProviderContract
 * {
 *     public function resolve(string $name, ?string $variant = null): string
 *     {
 *         $path = "/path/to/icons/{$variant}/{$name}.svg";
 *         return file_get_contents($path);
 *     }
 *
 *     public function available(): array
 *     {
 *         return ['icon-1', 'icon-2', 'icon-3'];
 *     }
 * }
 * ```
 */
interface IconProviderContract
{
    /**
     * Resolve an icon name to SVG content
     *
     * This method is responsible for loading the SVG content for a given icon name.
     * The SVG should be returned as a string without any wrapper elements.
     *
     * @param string $name The icon name (e.g., 'home', 'user', 'check')
     * @param string|null $variant Optional variant (e.g., 'solid', 'outline', 'mini')
     *
     * @return string The SVG content as a string
     *
     * @throws \InvalidArgumentException If the icon is not found
     */
    public function resolve(string $name, ?string $variant = null): string;

    /**
     * Get list of available icons
     *
     * Returns an array of available icon names. This can be used for:
     * - IDE autocomplete
     * - Icon picker UIs
     * - Validation
     *
     * @return array<string> Array of icon names
     */
    public function available(): array;

    /**
     * Check if an icon exists
     *
     * @param string $name The icon name to check
     * @param string|null $variant Optional variant to check
     *
     * @return bool True if the icon exists, false otherwise
     */
    public function has(string $name, ?string $variant = null): bool;
}
