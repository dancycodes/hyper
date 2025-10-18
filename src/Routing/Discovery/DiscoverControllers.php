<?php

namespace Dancycodes\Hyper\Routing\Discovery;

use Dancycodes\Hyper\Routing\RouteRegistrar;

/**
 * Controller Route Discovery Builder
 *
 * Configures and executes automatic route registration from controller classes
 * using PHP 8 attributes. Provides fluent API for specifying root namespace and
 * base path configuration before scanning directories for controllers with route
 * attributes.
 *
 * The discovery process delegates to RouteRegistrar which handles controller
 * reflection, attribute extraction, pending route creation, and final route
 * registration with Laravel's router.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 */
class DiscoverControllers
{
    /** @var string Base filesystem path for resolving relative controller directories */
    protected string $basePath = '';

    /** @var string Root namespace for controller class resolution */
    protected string $rootNamespace;

    /**
     * Initialize controller discovery builder with default configuration
     *
     * Sets root namespace to empty string and base path to Laravel application
     * root directory. Both values can be customized via fluent methods before
     * executing discovery.
     */
    public function __construct()
    {
        $this->rootNamespace = '';

        $this->basePath = base_path();
    }

    /**
     * Configure root namespace for controller class resolution
     *
     * Sets the base namespace used when converting filesystem paths to fully-qualified
     * controller class names during discovery. Typically set to application namespace
     * like 'App' or 'App\\Http\\Controllers'.
     *
     * @param string $rootNamespace Namespace prefix for discovered controllers
     *
     * @return static Returns this instance for method chaining
     */
    public function useRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }

    /**
     * Configure base filesystem path for directory resolution
     *
     * Sets the base path used when resolving relative controller directories during
     * discovery. Typically set to Laravel application root via base_path() or to
     * a specific subdirectory for scoped discovery.
     *
     * @param string $basePath Absolute filesystem path for base directory
     *
     * @return static Returns this instance for method chaining
     */
    public function useBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Execute controller discovery and route registration for directory
     *
     * Scans the specified directory for controller classes, extracts route attributes,
     * and registers routes with Laravel's router. Delegates actual discovery logic to
     * RouteRegistrar which handles reflection and attribute processing.
     *
     * @param string $directory Absolute or relative path to controller directory
     */
    public function in(string $directory): void
    {
        /** @phpstan-ignore-next-line */
        $router = app()->router;

        app(RouteRegistrar::class, [$router])
            ->useRootNamespace($this->rootNamespace)
            ->useBasePath($this->basePath)
            ->registerDirectory($directory);
    }
}
