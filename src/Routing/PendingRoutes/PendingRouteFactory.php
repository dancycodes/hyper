<?php

namespace Dancycodes\Hyper\Routing\PendingRoutes;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

/**
 * Pending Route Factory
 *
 * Creates PendingRoute instances from controller file information during route discovery.
 * Handles class name resolution from filesystem paths, reflection-based controller analysis,
 * abstract class filtering, public method extraction, and convention-based URI generation.
 *
 * Converts filesystem directory structure to namespaced class names using configured base
 * path and root namespace, instantiates reflection classes for validation and analysis,
 * and generates controller-level URIs from file locations with kebab-case conversion.
 *
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class PendingRouteFactory
{
    /**
     * Initialize factory with discovery configuration
     *
     * @param string $basePath Absolute filesystem base path for class resolution
     * @param string $rootNamespace Root namespace prefix for discovered controllers
     * @param string $registeringDirectory Directory being scanned for controllers
     */
    public function __construct(
        public string $basePath,
        protected string $rootNamespace,
        protected string $registeringDirectory
    ) {}

    /**
     * Create PendingRoute from controller file or null if ineligible
     *
     * Converts file path to fully-qualified class name, validates class exists and is
     * concrete (non-abstract), extracts public methods as PendingRouteAction instances,
     * generates controller URI from file location, and constructs PendingRoute with all
     * collected metadata.
     *
     * Returns null for abstract classes or when class cannot be resolved from file path,
     * allowing discovery to skip files that do not represent instantiable controllers.
     *
     * @param SplFileInfo $fileInfo Controller file information from filesystem scan
     *
     * @return PendingRoute|null Pending route instance or null if file is not valid controller
     */
    public function make(SplFileInfo $fileInfo): ?PendingRoute
    {
        $fullyQualifiedClassName = $this->fullyQualifiedClassNameFromFile($fileInfo);

        if (!class_exists($fullyQualifiedClassName)) {
            return null;
        }

        $class = new ReflectionClass($fullyQualifiedClassName);

        if ($class->isAbstract()) {
            return null;
        }

        $actions = collect($class->getMethods())
            ->filter(function (ReflectionMethod $method) {
                return $method->isPublic();
            })
            ->map(function (ReflectionMethod $method) use ($fullyQualifiedClassName) {
                return new PendingRouteAction($method, $fullyQualifiedClassName);
            });

        $uri = $this->discoverUri($class);

        return new PendingRoute($fileInfo, $class, $uri, $fullyQualifiedClassName, $actions);
    }

    /**
     * Generate controller URI from class file location
     *
     * Extracts relative path from registering directory to controller file, strips
     * 'Controller' suffix, converts path segments to kebab-case, and joins with forward
     * slashes. Filters out 'index' segments to prevent redundant URI components.
     *
     * @param ReflectionClass<object> $class Controller reflection class
     *
     * @return string Generated controller URI path
     */
    protected function discoverUri(ReflectionClass $class): string
    {
        $parts = Str::of((string) $class->getFileName())
            ->after(str_replace('/', DIRECTORY_SEPARATOR, $this->registeringDirectory))
            ->beforeLast('Controller')
            ->explode(DIRECTORY_SEPARATOR);

        return collect($parts)
            ->filter()
            ->reject(function (string $part) {
                return strtolower($part) === 'index';
            })
            ->map(fn (string $part) => Str::of($part)->kebab())
            ->implode('/');
    }

    /**
     * Convert file path to fully-qualified controller class name
     *
     * Strips base path from file's absolute path, removes .php extension, replaces
     * directory separators with namespace separators, converts to PascalCase, and
     * prepends root namespace. Handles App namespace replacement for Laravel
     * application namespace resolution.
     *
     * @param SplFileInfo $file Controller file information
     *
     * @return string Fully-qualified controller class name
     */
    protected function fullyQualifiedClassNameFromFile(SplFileInfo $file): string
    {
        $class = trim(Str::replaceFirst($this->basePath, '', (string) $file->getRealPath()), DIRECTORY_SEPARATOR);

        $class = str_replace(
            [DIRECTORY_SEPARATOR, 'App\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );

        return $this->rootNamespace . $class;
    }
}
