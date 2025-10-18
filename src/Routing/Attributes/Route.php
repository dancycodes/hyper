<?php

namespace Dancycodes\Hyper\Routing\Attributes;

use Attribute;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;

/**
 * Route Definition Attribute
 *
 * Configures route registration parameters for controller methods during route
 * discovery. Supports HTTP method specification, URI customization, middleware
 * attachment, route naming, domain binding, and soft-delete model binding.
 *
 * When applied to a controller class, configuration cascades to all methods unless
 * overridden by method-level attributes. When applied to individual methods, provides
 * granular control over specific route definitions.
 *
 * HTTP methods are normalized to uppercase and validated against Laravel's Router
 * verb list. Invalid HTTP methods are silently filtered during attribute construction.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 * @see \Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleHttpMethodsAttribute
 * @see \Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleUriAttribute
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route implements DiscoveryAttribute
{
    /** @var array<int, string> Normalized uppercase HTTP method names */
    public array $methods;

    /** @var array<int, class-string> Middleware class names or aliases */
    public array $middleware;

    /**
     * Initialize route definition attribute
     *
     * @param array<int, string>|string $method HTTP methods (GET, POST, etc.) as array or single string
     * @param string|null $uri Custom URI pattern for route, or null for auto-generated URI
     * @param string|null $fullUri Complete URI override bypassing all transformers
     * @param string|null $name Route name for Laravel route() helper
     * @param array<int, class-string>|string $middleware Middleware to apply to route
     * @param string|null $domain Domain constraint for multi-tenant routing
     * @param bool $withTrashed Whether to include soft-deleted models in route model binding
     */
    public function __construct(
        array|string $method = [],
        public ?string $uri = null,
        public ?string $fullUri = null,
        public ?string $name = null,
        array|string $middleware = [],
        public ?string $domain = null,
        public bool $withTrashed = false,
    ) {
        $methods = Arr::wrap($method);

        /** @var array<int, string> $processedMethods */
        $processedMethods = collect($methods)
            ->map(fn (string $method) => strtoupper($method))
            ->filter(fn (string $method) => in_array($method, Router::$verbs))
            ->toArray();

        $this->methods = $processedMethods;
        $this->middleware = Arr::wrap($middleware);
    }
}
