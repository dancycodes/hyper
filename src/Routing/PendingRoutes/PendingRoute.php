<?php

namespace Dancycodes\Hyper\Routing\PendingRoutes;

use Dancycodes\Hyper\Routing\Attributes\DiscoveryAttribute;
use Dancycodes\Hyper\Routing\Attributes\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use SplFileInfo;

/**
 * Pending Route Representation
 *
 * Represents controller-level route configuration extracted during route discovery
 * before final registration with Laravel's router. Contains file metadata, reflection
 * information, computed URI segments, and collection of method-level pending actions.
 *
 * Provides methods for namespace inspection, controller naming analysis, child namespace
 * computation for nested controllers, and attribute extraction via PHP 8 reflection.
 *
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteFactory
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction
 */
class PendingRoute
{
    /**
     * Initialize pending route with controller metadata
     *
     * @param SplFileInfo $fileInfo Controller file information from filesystem scan
     * @param ReflectionClass<object> $class Reflection instance for controller class
     * @param string $uri Base URI segment derived from controller file location
     * @param string $fullyQualifiedClassName Complete controller class name with namespace
     * @param Collection<int, PendingRouteAction> $actions Collection of method-level pending actions
     */
    public function __construct(
        public SplFileInfo $fileInfo,
        public ReflectionClass $class,
        public string $uri,
        public string $fullyQualifiedClassName,
        public Collection $actions,
    ) {}

    /**
     * Extract namespace from fully-qualified controller class name
     *
     * Removes controller class name from fully-qualified name to return parent namespace.
     * Used for identifying parent-child controller relationships in nested structures.
     *
     * @return string Controller namespace without trailing class name
     */
    public function namespace(): string
    {
        return Str::beforeLast($this->fullyQualifiedClassName, '\\');
    }

    /**
     * Extract short controller name without 'Controller' suffix
     *
     * Extracts controller class name from fully-qualified name and removes 'Controller'
     * suffix. Used for constructing child namespaces and URI segments.
     *
     * @return string Controller name without namespace or 'Controller' suffix
     */
    public function shortControllerName(): string
    {
        return Str::of($this->fullyQualifiedClassName)
            ->afterLast('\\')
            ->beforeLast('Controller');
    }

    /**
     * Compute child controller namespace for nested resource discovery
     *
     * Constructs expected namespace for child controllers in nested resource hierarchies
     * by appending short controller name to current namespace. Used by nested controller
     * transformer to identify parent-child relationships.
     *
     * @return string Expected namespace for child controllers
     */
    public function childNamespace(): string
    {
        return $this->namespace() . '\\' . $this->shortControllerName();
    }

    /**
     * Retrieve Route attribute instance if present on controller class
     *
     * Convenience method for accessing Route attribute specifically. Returns first Route
     * attribute instance or null if controller has no Route attribute.
     *
     * @return DiscoveryAttribute|null Route attribute instance or null
     */
    public function getRouteAttribute(): ?DiscoveryAttribute
    {
        return $this->getAttribute(Route::class);
    }

    /**
     * Retrieve discovery attribute instance by class name
     *
     * Uses reflection to extract first attribute matching specified class from controller
     * class. Searches for attribute class or any implementing subclass via IS_INSTANCEOF
     * flag. Returns instantiated attribute object or null if not found.
     *
     * @template TDiscoveryAttribute of DiscoveryAttribute
     *
     * @param class-string<TDiscoveryAttribute> $attributeClass Attribute class to search for
     *
     * @return DiscoveryAttribute|null Instantiated attribute or null
     */
    public function getAttribute(string $attributeClass): ?DiscoveryAttribute
    {
        $attributes = $this->class->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        if (!count($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
