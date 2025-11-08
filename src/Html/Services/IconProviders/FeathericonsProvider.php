<?php

namespace Dancycodes\Hyper\Html\Services\IconProviders;

use Dancycodes\Hyper\Html\Contracts\IconProviderContract;
use Illuminate\Support\Facades\File;

/**
 * Feathericons Icon Provider
 *
 * Built-in provider for Feather Icons (https://feathericons.com).
 * Feather Icons is a collection of simply beautiful open source icons.
 *
 * This provider works with Blade UI Kit Feather Icons packages:
 * - brunocfalcao/blade-feather-icons
 * - outhebox/blade-feather-icons
 *
 * Install via: `composer require brunocfalcao/blade-feather-icons`
 * Or: `composer require outhebox/blade-feather-icons`
 *
 * Icon Naming Convention:
 * - Simple format: "alert-triangle", "arrow-up", "home"
 * - No variants (Feather Icons has single style)
 *
 * Directory Structure:
 * All icons are in a flat resources/svg directory:
 * - alert-triangle.svg
 * - arrow-up.svg
 * - home.svg
 * - etc.
 *
 * Usage Examples:
 * ```php
 * // Using simple name
 * Icon::make('home', 'feathericons');
 * Icon::make('alert-triangle', 'feathericons');
 *
 * // In button
 * Html::button()->leftIcon('home', 'feathericons')->text('Dashboard');
 * ```
 */
class FeathericonsProvider implements IconProviderContract
{
    protected string $basePath;

    public function __construct()
    {
        // Try common Feather Icons package paths
        $possiblePaths = [
            base_path('vendor/brunocfalcao/blade-feather-icons/resources/svg'),
            base_path('vendor/outhebox/blade-feather-icons/resources/svg'),
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $this->basePath = $path;

                return;
            }
        }

        // Default to first path (will fail on resolve if not installed)
        $this->basePath = $possiblePaths[0];
    }

    /**
     * Resolve icon name to SVG content
     *
     * @param string $name Icon name (e.g., 'home', 'alert-triangle', 'arrow-up')
     * @param string|null $variant Not used for Feather Icons (single style only)
     *
     * @throws \InvalidArgumentException If icon not found
     *
     * @return string SVG content
     */
    public function resolve(string $name, ?string $variant = null): string
    {
        // Build path to SVG file (flat structure)
        $path = "{$this->basePath}/{$name}.svg";

        // Check if file exists
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(
                "Feather Icon '{$name}' not found. " .
                "Path: {$path}. " .
                'Make sure a Feather Icons package is installed via Composer.'
            );
        }

        // Load and return SVG content
        return file_get_contents($path);
    }

    /**
     * Check if an icon exists
     *
     * @param string $name Icon name
     * @param string|null $variant Not used for Feather Icons
     */
    public function has(string $name, ?string $variant = null): bool
    {
        try {
            $this->resolve($name, $variant);

            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get all available icons
     *
     * Scans the Feather Icons directory and returns all available icon names.
     *
     * @return array<string> Array of icon names
     */
    public function available(): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }

        $icons = [];
        $files = File::files($this->basePath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'svg') {
                $icons[] = $file->getFilenameWithoutExtension();
            }
        }

        sort($icons);

        return $icons;
    }

    /**
     * Get the base path where Feather Icons are installed
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Check if Feather Icons package is installed
     */
    public function isInstalled(): bool
    {
        return is_dir($this->basePath);
    }
}
