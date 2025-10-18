<?php

namespace Dancycodes\Hyper\Routing;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteFactory;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Route Registrar - Attribute-Based Route Discovery Engine
 *
 * Orchestrates automatic route registration from controller classes using PHP 8 attributes.
 * Recursively scans directories for controllers, creates pending route representations,
 * applies transformation pipeline for URI construction and attribute processing, and
 * registers final routes with Laravel's router.
 *
 * Discovery process: filesystem scan → PendingRoute creation → transformer pipeline →
 * Laravel router registration. Configurable via base path, root namespace, and transformer
 * pipeline defined in hyper.route_discovery.pending_route_transformers configuration.
 *
 * Supports nested controller hierarchies with recursive directory scanning. Each directory
 * level is processed independently before recursing into subdirectories.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\DiscoverControllers
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteFactory
 * @see \Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer
 */
class RouteRegistrar
{
    /** @var Router Laravel router instance for route registration */
    private Router $router;

    /** @var string Base filesystem path for resolving controller directories */
    protected string $basePath;

    /** @var string Root namespace for controller class resolution */
    protected string $rootNamespace = '';

    /** @var string Currently registering directory path */
    protected string $registeringDirectory = '';

    /**
     * Initialize route registrar with Laravel router
     *
     * Sets router instance and defaults base path to Laravel application directory.
     * Base path and root namespace can be customized via fluent methods before
     * executing discovery.
     *
     * @param Router $router Laravel router instance
     */
    public function __construct(Router $router)
    {
        $this->router = $router;

        $this->basePath = app()->path();
    }

    /**
     * Configure base filesystem path for directory resolution
     *
     * Sets the base path used when resolving relative controller directories and
     * converting file paths to class names. Typically set to Laravel application
     * root or specific subdirectory for scoped discovery.
     *
     * @param string $basePath Absolute filesystem path
     *
     * @return static Returns this instance for method chaining
     */
    public function useBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Configure root namespace for controller class resolution
     *
     * Sets the base namespace prepended when converting filesystem paths to
     * fully-qualified controller class names. Typically set to application
     * namespace like 'App' or 'App\\Http\\Controllers'.
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
     * Execute route discovery and registration for directory
     *
     * Recursively scans directory for PHP controller files, converts to pending routes
     * via factory, applies transformer pipeline, and registers with Laravel router.
     * Entry point for route discovery process.
     *
     * @param string $directory Absolute or relative path to controller directory
     */
    public function registerDirectory(string $directory): void
    {
        $this->registeringDirectory = $directory;

        $pendingRoutes = $this->convertToPendingRoutes($directory);

        $pendingRoutes = $this->transformPendingRoutes($pendingRoutes);

        $this->registerRoutes($pendingRoutes);
    }

    /**
     * Convert directory contents to pending route collection
     *
     * Scans directory for .php files at current depth level, creates PendingRoute instances
     * via factory, then recursively processes subdirectories. Filters null results from
     * factory (abstract classes, non-existent classes). Returns flattened collection of
     * all pending routes from directory tree.
     *
     * @param string $directory Directory path to scan
     *
     * @return Collection<int, PendingRoute> Collection of pending routes from directory
     */
    protected function convertToPendingRoutes(string $directory): Collection
    {
        $files = (new Finder)->files()->depth(0)->name('*.php')->in($directory);

        $pendingRouteFactory = new PendingRouteFactory(
            $this->basePath,
            $this->rootNamespace,
            $this->registeringDirectory,
        );

        /** @var Collection<int, SplFileInfo> $fileCollection */
        $fileCollection = collect($files);

        $pendingRoutes = $fileCollection
            ->map(fn (SplFileInfo $file) => $pendingRouteFactory->make($file))
            ->filter();

        /** @var Collection<int, SplFileInfo> $directoryCollection */
        $directoryCollection = collect((new Finder)->directories()->depth(0)->in($directory));

        $directoryCollection
            ->flatMap(function (SplFileInfo $subDirectory) {
                return $this->convertToPendingRoutes($subDirectory->getPathname());
            })
            ->filter()
            ->each(fn (PendingRoute $pendingRoute) => $pendingRoutes->push($pendingRoute));

        return $pendingRoutes->values();
    }

    /**
     * Apply transformation pipeline to pending routes collection
     *
     * Retrieves transformer class names from configuration, instantiates each via service
     * container, and applies transformers sequentially to pending routes collection. Each
     * transformer receives output from previous transformer, enabling pipeline composition.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes
     */
    protected function transformPendingRoutes(Collection $pendingRoutes): Collection
    {
        /** @var array<int, class-string<PendingRouteTransformer>> $transformers */
        $transformers = config('hyper.route_discovery.pending_route_transformers');

        /** @var Collection<int, PendingRouteTransformer> */
        $transformers = collect($transformers)
            ->map(fn (string $transformerClass): PendingRouteTransformer => app($transformerClass));

        foreach ($transformers as $transformer) {
            $pendingRoutes = $transformer->transform($pendingRoutes);
        }

        return $pendingRoutes;
    }

    /**
     * Register pending routes with Laravel router
     *
     * Iterates through pending routes and their actions, registering each action with
     * Laravel's router using addRoute() method. Applies middleware, route names, parameter
     * constraints, domain constraints, and soft-delete configuration from pending action
     * properties.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to register
     */
    protected function registerRoutes(Collection $pendingRoutes): void
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) {
                $route = $this->router->addRoute($action->methods, $action->uri, $action->action());

                $route->middleware($action->middleware);

                if ($action->name !== null) {
                    $route->name($action->name);
                }

                if (count($action->wheres)) {
                    $route->setWheres($action->wheres);
                }

                if ($action->domain) {
                    $route->domain($action->domain);
                }

                if ($action->withTrashed) {
                    $route->withTrashed($action->withTrashed);
                }
            });
        });
    }
}
