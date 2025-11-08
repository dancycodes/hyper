<?php

namespace Dancycodes\Hyper\Html\Concerns\Assets;

use Closure;

/**
 * Asset management helpers for framework integration
 *
 * Provides chainable methods to inject framework asset tags (Vite, Hyper, etc.)
 * These methods replicate Blade directives but in pure PHP, enabling developers
 * to use the HTML Builder without mixing Blade syntax.
 *
 * All methods inject raw HTML content and return the element for method chaining.
 */
trait ManagesAssets
{
    /**
     * Inject Laravel Vite asset tags (replicates @vite directive)
     *
     * Automatically includes the necessary script and link tags for Vite assets.
     * In development, connects to the Vite dev server. In production, loads
     * the built assets from the manifest.
     *
     * Example:
     * ```php
     * Html::head()
     *     ->vite(['resources/css/app.css', 'resources/js/app.js'])
     *     ->content(Html::title('My App'));
     * ```
     *
     * @param string|array|Closure $entrypoints File paths to Vite entry points or closure returning paths
     * @param string|null|Closure $buildDirectory Optional build directory or closure returning directory
     */
    public function vite(string|array|Closure $entrypoints = [], string|null|Closure $buildDirectory = null): static
    {
        // Evaluate closures if provided
        $entrypoints = $this->evaluate($entrypoints);
        $buildDirectory = $buildDirectory ? $this->evaluate($buildDirectory) : null;

        // Get Vite instance and generate HTML
        $vite = app(\Illuminate\Foundation\Vite::class);
        $html = $vite($entrypoints, $buildDirectory);

        // Inject as raw HTML (accumulating if rawHtml already exists)
        return $this->appendHtml($html);
    }

    /**
     * Inject Laravel Hyper runtime assets (replicates @hyper directive)
     *
     * Injects the CSRF token meta tag and Hyper's JavaScript module.
     * This is required for Hyper's reactive functionality to work.
     *
     * Generates:
     * - <meta name="csrf-token" content="{token}">
     * - <script type="module" src="/vendor/hyper/js/hyper.js"></script>
     *
     * Example:
     * ```php
     * Html::head()
     *     ->hyper()
     *     ->vite(['resources/css/app.css', 'resources/js/app.js'])
     *     ->content(Html::title('My App'));
     * ```
     */
    public function hyper(): static
    {
        // Build the HTML exactly as the @hyper directive does
        $csrfToken = csrf_token();
        $scriptSrc = asset('vendor/hyper/js/hyper.js');

        // Escape values for XSS protection
        $escapedToken = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');
        $escapedSrc = htmlspecialchars($scriptSrc, ENT_QUOTES, 'UTF-8');

        $html = '<meta name="csrf-token" content="' . $escapedToken . '">' . "\n" .
            '<script type="module" src="' . $escapedSrc . '"></script>';

        // Inject as raw HTML (accumulating if rawHtml already exists)
        return $this->appendHtml($html);
    }

    /**
     * Inject Tailwind CSS from CDN (development/prototyping only)
     *
     * This is a convenience method for quick prototyping. For production,
     * use Tailwind CLI or Vite integration instead.
     *
     * Version 4 is the default and uses the new @tailwindcss/browser package.
     * Version 3 uses the legacy Play CDN.
     *
     * Example:
     * ```php
     * Html::head()
     *     ->hyper()
     *     ->tailwindCdn()  // Uses v4 by default
     *     ->content(Html::title('Prototype'));
     *
     * // Or specify version explicitly:
     * Html::head()->tailwindCdn('3')  // Uses v3 legacy CDN
     * ```
     *
     * @param string|Closure $version Tailwind major version ('3' or '4', defaults to '4')
     */
    public function tailwindCdn(string|Closure $version = '4'): static
    {
        $version = $this->evaluate($version);

        // Generate correct CDN URL based on version
        $html = match ($version) {
            '3' => '<script src="https://cdn.tailwindcss.com"></script>',
            '4' => '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>',
            default => '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>',
        };

        // Inject as raw HTML (accumulating if rawHtml already exists)
        return $this->appendHtml($html);
    }

    /**
     * Append HTML to existing rawHtml instead of replacing it
     *
     * This allows multiple asset injection methods to be chained
     * without overwriting each other.
     *
     * @param string $html HTML to append
     */
    protected function appendHtml(string $html): static
    {
        // If rawHtml already exists, append with newline separator
        if (isset($this->rawHtml) && $this->rawHtml !== null) {
            $this->rawHtml .= "\n" . $html;
        } else {
            $this->rawHtml = $html;
        }

        return $this;
    }
}
