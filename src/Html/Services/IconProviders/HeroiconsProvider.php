<?php

namespace Dancycodes\Hyper\Html\Services\IconProviders;

use Dancycodes\Hyper\Html\Contracts\IconProviderContract;
use Illuminate\Support\Facades\File;

/**
 * Heroicons Icon Provider
 *
 * Built-in provider for Heroicons (https://heroicons.com).
 * Supports Heroicons v2 with three variants: outline, solid, and mini (20px).
 *
 * This provider works with the blade-ui-kit/blade-heroicons Composer package.
 * Install via: `composer require blade-ui-kit/blade-heroicons`
 *
 * Icon Naming Conventions:
 * - Full format: "heroicon-o-home" (o=outline, s=solid, m=mini)
 * - Short format: "home" (defaults to outline variant)
 *
 * Directory Structure:
 * blade-ui-kit/blade-heroicons uses a FLAT structure where all icons are in resources/svg:
 * - o-icon-name.svg (outline)
 * - s-icon-name.svg (solid)
 * - m-icon-name.svg (mini)
 *
 * Usage Examples:
 * ```php
 * // Using full name
 * Icon::make('heroicon-s-home');
 *
 * // Using short name with variant
 * Icon::make('home', 'heroicons')->solid();
 *
 * // In button
 * Html::button()->leftIcon('heroicon-o-arrow-left')->text('Back');
 * ```
 */
class HeroiconsProvider implements IconProviderContract
{
    protected string $basePath;

    /**
     * Variant mapping for Heroicons naming convention
     */
    protected const VARIANT_MAP = [
        'o' => 'o',
        'outline' => 'o',
        's' => 's',
        'solid' => 's',
        'm' => 'm',
        'mini' => 'm',
        'c' => 'c',
        'micro' => 'c',
    ];

    public function __construct()
    {
        // blade-ui-kit/blade-heroicons path (flat structure with prefixes)
        $bladeuiPath = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg');

        if (is_dir($bladeuiPath)) {
            $this->basePath = $bladeuiPath;

            return;
        }

        // If not found, set default path (will fail on resolve if not installed)
        $this->basePath = $bladeuiPath;
    }

    /**
     * Resolve icon name to SVG content
     *
     * Supports multiple naming formats:
     * - "heroicon-o-home" (full format with variant prefix)
     * - "home" with variant parameter (short format)
     *
     * @param string $name Icon name
     * @param string|null $variant Variant (outline, solid, mini, micro)
     *
     * @throws \InvalidArgumentException If icon not found
     *
     * @return string SVG content
     */
    public function resolve(string $name, ?string $variant = null): string
    {
        // Parse name if it includes the heroicon prefix
        if (str_starts_with($name, 'heroicon-')) {
            [$prefix, $variantCode, $iconName] = $this->parseHeroiconName($name);
            $variant = $variantCode;
            $name = $iconName;
        }

        // Default to outline if no variant specified
        $variant = $variant ?? 'outline';

        // Normalize variant to prefix (o, s, m, c)
        $variantPrefix = self::VARIANT_MAP[$variant] ?? 'o';

        // Build path for FLAT structure with prefix (e.g., o-home.svg)
        $path = "{$this->basePath}/{$variantPrefix}-{$name}.svg";

        // Check if file exists
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(
                "Heroicon '{$name}' not found in variant '{$variant}'. " .
                "Path: {$path}. " .
                'Make sure blade-ui-kit/blade-heroicons is installed via Composer.'
            );
        }

        // Load and return SVG content
        return file_get_contents($path);
    }

    /**
     * Check if an icon exists
     *
     * @param string $name Icon name
     * @param string|null $variant Variant (outline, solid, mini)
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
     * Scans the Heroicons directory and returns all available icon names.
     * For blade-ui-kit/blade-heroicons (flat structure), extracts names from prefixed files.
     *
     * @return array<string> Array of icon names (without variant prefix)
     */
    public function available(): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }

        $icons = [];

        // Scan flat directory for files with variant prefixes
        $files = File::files($this->basePath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'svg') {
                $filename = $file->getFilenameWithoutExtension();

                // Extract icon name from prefixed filename (e.g., "o-home" -> "home")
                if (preg_match('/^([osmc])-(.+)$/', $filename, $matches)) {
                    $iconName = $matches[2]; // The name without prefix
                    $icons[] = $iconName;
                }
            }
        }

        // Return unique icon names (same icon exists in multiple variants)
        return array_unique($icons);
    }

    /**
     * Parse Heroicon full name format
     *
     * Converts "heroicon-o-arrow-left" to ["heroicon", "o", "arrow-left"]
     *
     * @param string $name Full heroicon name
     *
     * @return array{0: string, 1: string, 2: string}
     */
    protected function parseHeroiconName(string $name): array
    {
        // heroicon-o-arrow-left -> ["heroicon", "o", "arrow-left"]
        $parts = explode('-', $name, 3);

        if (count($parts) < 3) {
            throw new \InvalidArgumentException(
                "Invalid Heroicon name format: '{$name}'. " .
                "Expected format: 'heroicon-{variant}-{name}' (e.g., 'heroicon-o-home')"
            );
        }

        return $parts;
    }

    /**
     * Get the base path where Heroicons are installed
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Check if Heroicons package is installed
     */
    public function isInstalled(): bool
    {
        return is_dir($this->basePath);
    }
}
