<?php

namespace Dancycodes\Hyper\Html\Services;

use Dancycodes\Hyper\Html\Contracts\IconProviderContract;
use Illuminate\Support\Facades\Cache;

/**
 * Icon Manager Service
 *
 * Central service for managing icon providers and resolving icons.
 * Handles provider registration, icon resolution, and caching.
 *
 * Features:
 * - Multiple provider support (Heroicons, Font Awesome, custom, etc.)
 * - Automatic caching (forever in production, disabled in development)
 * - Provider auto-discovery
 * - Default provider fallback
 *
 * Registration Examples:
 * ```php
 * // Via Html facade (recommended)
 * Html::iconProvider('heroicons', HeroiconsProvider::class);
 *
 * // Via service container
 * $manager = app(IconManager::class);
 * $manager->register('my-icons', new MyIconProvider());
 * ```
 *
 * Usage Examples:
 * ```php
 * // Resolve icon using default provider
 * $svg = $manager->resolve('home');
 *
 * // Resolve icon using specific provider
 * $svg = $manager->resolve('home', 'heroicons', 'solid');
 * ```
 */
class IconManager
{
    /**
     * Registered icon providers
     *
     * @var array<string, IconProviderContract>
     */
    protected array $providers = [];

    /**
     * Default provider name (used when no provider specified)
     */
    protected ?string $defaultProvider = null;

    /**
     * Cache key prefix for icon caching
     */
    protected const CACHE_PREFIX = 'hyper.icon';

    /**
     * Register an icon provider
     *
     * Providers can be registered as:
     * - Class string: Will be resolved from container
     * - Instance: Will be used directly
     *
     * The first registered provider becomes the default.
     *
     * @param string $name Provider name (e.g., 'heroicons', 'fontawesome')
     * @param string|IconProviderContract $provider Provider class name or instance
     *
     * @throws \InvalidArgumentException If provider doesn't implement IconProviderContract
     *
     * @return $this
     */
    public function register(string $name, string|IconProviderContract $provider): static
    {
        // Resolve from container if class string
        if (is_string($provider)) {
            $provider = app($provider);
        }

        // Validate provider implements contract
        if (!$provider instanceof IconProviderContract) {
            throw new \InvalidArgumentException(
                'Icon provider must implement IconProviderContract. ' .
                'Got: ' . get_class($provider)
            );
        }

        $this->providers[$name] = $provider;

        // Set as default if first provider
        if ($this->defaultProvider === null) {
            $this->defaultProvider = $name;
        }

        return $this;
    }

    /**
     * Set the default icon provider
     *
     * @param string $name Provider name
     *
     * @throws \InvalidArgumentException If provider not registered
     *
     * @return $this
     */
    public function setDefaultProvider(string $name): static
    {
        if (!$this->hasProvider($name)) {
            throw new \InvalidArgumentException(
                "Cannot set default provider '{$name}': Provider not registered."
            );
        }

        $this->defaultProvider = $name;

        return $this;
    }

    /**
     * Get the default provider name
     */
    public function getDefaultProvider(): ?string
    {
        return $this->defaultProvider;
    }

    /**
     * Resolve an icon to SVG content
     *
     * Smart auto-discovery behavior:
     * 1. If provider specified → use that provider
     * 2. If no provider specified → try default provider first
     * 3. If default doesn't have it → search all providers
     * 4. If no providers found → throw exception
     *
     * Caching behavior:
     * - Production: Forever cache (cleared on deployment)
     * - Development: No cache (immediate updates)
     *
     * @param string $name Icon name
     * @param string|null $provider Provider name (null = auto-discover)
     * @param string|null $variant Icon variant (e.g., 'solid', 'outline')
     *
     * @throws \InvalidArgumentException If provider not found or icon not found
     *
     * @return string SVG content
     */
    public function resolve(string $name, ?string $provider = null, ?string $variant = null): string
    {
        // If provider specified explicitly, use it
        if ($provider !== null) {
            if (!$this->hasProvider($provider)) {
                throw new \InvalidArgumentException(
                    "Icon provider '{$provider}' not registered. " .
                    'Available providers: ' . implode(', ', array_keys($this->providers))
                );
            }

            return $this->resolveWithCache($provider, $name, $variant);
        }

        // No provider specified - use smart auto-discovery
        // Try default provider first
        if ($this->defaultProvider !== null) {
            try {
                return $this->resolveWithCache($this->defaultProvider, $name, $variant);
            } catch (\InvalidArgumentException $e) {
                // Default provider doesn't have this icon, continue to search all providers
            }
        }

        // Search all providers for this icon
        $foundProvider = $this->findProviderForIcon($name, $variant);

        if ($foundProvider !== null) {
            return $this->resolveWithCache($foundProvider, $name, $variant);
        }

        // Icon not found in any provider
        throw new \InvalidArgumentException(
            "Icon '{$name}' not found in any registered provider. " .
            'Available providers: ' . implode(', ', array_keys($this->providers)) . '. ' .
            'Make sure the icon exists and the provider is installed.'
        );
    }

    /**
     * Find which provider has a specific icon
     *
     * Searches through all registered providers to find one that has the icon.
     * Returns the first provider that has it.
     *
     * @param string $name Icon name
     * @param string|null $variant Icon variant
     *
     * @return string|null Provider name if found, null otherwise
     */
    protected function findProviderForIcon(string $name, ?string $variant = null): ?string
    {
        foreach ($this->providers as $providerName => $providerInstance) {
            if ($providerInstance->has($name, $variant)) {
                return $providerName;
            }
        }

        return null;
    }

    /**
     * Resolve icon with caching
     *
     * @param string $provider Provider name
     * @param string $name Icon name
     * @param string|null $variant Icon variant
     *
     * @return string SVG content
     */
    protected function resolveWithCache(string $provider, string $name, ?string $variant): string
    {
        // Build cache key
        $cacheKey = $this->buildCacheKey($provider, $name, $variant);

        // Cache in production, no cache in development
        if (app()->environment('production')) {
            return Cache::rememberForever($cacheKey, function () use ($provider, $name, $variant) {
                return $this->resolveFromProvider($provider, $name, $variant);
            });
        }

        return $this->resolveFromProvider($provider, $name, $variant);
    }

    /**
     * Check if an icon exists
     *
     * @param string $name Icon name
     * @param string|null $provider Provider name (null = use default)
     * @param string|null $variant Icon variant
     */
    public function has(string $name, ?string $provider = null, ?string $variant = null): bool
    {
        try {
            $this->resolve($name, $provider, $variant);

            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Check if a provider is registered
     *
     * @param string $name Provider name
     */
    public function hasProvider(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * Get a registered provider
     *
     * @param string $name Provider name
     */
    public function getProvider(string $name): ?IconProviderContract
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all registered providers
     *
     * @return array<string, IconProviderContract>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Clear icon cache
     *
     * Useful when icons are updated or for cache warming.
     *
     * @param string|null $provider Clear specific provider or all providers
     *
     * @return $this
     */
    public function clearCache(?string $provider = null): static
    {
        if ($provider !== null) {
            // Clear specific provider's cache
            Cache::forget(self::CACHE_PREFIX . ".{$provider}.*");
        } else {
            // Clear all icon caches
            Cache::forget(self::CACHE_PREFIX . '.*');
        }

        return $this;
    }

    /**
     * Resolve icon from provider (internal)
     *
     * @param string $provider Provider name
     * @param string $name Icon name
     * @param string|null $variant Icon variant
     *
     * @throws \InvalidArgumentException If icon not found
     *
     * @return string SVG content
     */
    protected function resolveFromProvider(string $provider, string $name, ?string $variant): string
    {
        return $this->providers[$provider]->resolve($name, $variant);
    }

    /**
     * Build cache key for icon
     *
     * @param string $provider Provider name
     * @param string $name Icon name
     * @param string|null $variant Icon variant
     *
     * @return string Cache key
     */
    protected function buildCacheKey(string $provider, string $name, ?string $variant): string
    {
        $parts = [self::CACHE_PREFIX, $provider, $name];

        if ($variant !== null) {
            $parts[] = $variant;
        }

        return implode('.', $parts);
    }
}
