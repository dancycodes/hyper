<?php

namespace Dancycodes\Hyper\Routing\PendingRoutes;

use Dancycodes\Hyper\Routing\Attributes\DiscoveryAttribute;
use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\Attributes\Where;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Pending Route Action Representation
 *
 * Represents method-level route configuration extracted during route discovery before
 * final registration with Laravel's router. Contains reflection metadata, computed URI
 * segments, HTTP methods, middleware, parameter constraints, and all route properties
 * required for registration.
 *
 * Generates URIs from method signatures by analyzing parameters, inferring HTTP methods
 * from method names following Laravel conventions, extracting model parameters for route
 * model binding, and handling optional parameters with proper syntax.
 *
 * Provides fluent methods for accumulating middleware and parameter constraints during
 * transformation pipeline processing.
 *
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute
 * @see \Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteFactory
 */
class PendingRouteAction
{
    /** @var ReflectionMethod Controller method reflection instance */
    public ReflectionMethod $method;

    /** @var string Complete route URI with parameters */
    public string $uri;

    /** @var array<int, string> HTTP method verbs (GET, POST, PUT, etc.) */
    public array $methods = [];

    /** @var Collection<int, ReflectionParameter> Eloquent Model parameters for route binding */
    public Collection $modelParameters;

    /** @var array{class-string, string} Controller class and method name tuple */
    public array $action;

    /** @var array<int, class-string> Middleware class names or aliases */
    public array $middleware = [];

    /** @var array<string, string> Parameter name to regex constraint mapping */
    public array $wheres = [];

    /** @var string|null Route name for Laravel route() helper */
    public ?string $name = null;

    /** @var string|null Domain constraint for multi-tenant routing */
    public ?string $domain = null;

    /** @var bool Whether to include soft-deleted models in route binding */
    public bool $withTrashed = false;

    /**
     * Initialize pending route action from controller method reflection
     *
     * Analyzes method signature to extract model parameters, generate relative URI with
     * optional parameter syntax, infer HTTP methods from method name, and construct
     * controller action tuple for Laravel router registration.
     *
     * @param ReflectionMethod $method Controller method reflection instance
     * @param class-string $controllerClass Fully-qualified controller class name
     */
    public function __construct(ReflectionMethod $method, string $controllerClass)
    {
        $this->method = $method;

        $this->modelParameters = $this->modelParameters();

        $this->uri = $this->relativeUri();

        $this->methods = $this->discoverHttpMethods();

        $this->action = [$controllerClass, $method->name];
    }

    /**
     * Extract Eloquent Model parameters from method signature for route binding
     *
     * Filters method parameters to identify those type-hinted with Eloquent Model classes
     * or subclasses. These parameters will be automatically resolved via Laravel's route
     * model binding when the route is matched.
     *
     * @return Collection<int, ReflectionParameter> Collection of Model-typed parameters
     */
    public function modelParameters(): Collection
    {
        return collect($this->method->getParameters())->filter(function (ReflectionParameter $parameter) {
            $type = $parameter->getType();

            return $type instanceof ReflectionNamedType && is_a($type->getName(), Model::class, true);
        });
    }

    /**
     * Generate relative URI from method name and parameters
     *
     * Constructs URI segment from method name (converted to kebab-case) unless method name
     * matches common controller patterns (index, __invoke, etc.), then appends parameter
     * placeholders for URL parameters. Handles optional parameters with proper ? syntax and
     * validates parameter order (optional parameters must follow required parameters).
     *
     * @return string Relative URI with parameter placeholders
     */
    public function relativeUri(): string
    {
        $uri = '';

        if (!in_array($this->method->getName(), $this->commonControllerMethodNames())) {
            $uri = Str::kebab($this->method->getName());
        }

        $urlParameters = $this->getUrlParameters();

        if ($urlParameters->isNotEmpty()) {
            if ($uri !== '') {
                $uri .= '/';
            }

            $parameterSegments = [];
            $hasOptional = false;

            /** @var ReflectionParameter $parameter */
            foreach ($urlParameters as $parameter) {
                $paramName = $parameter->getName();

                if ($parameter->isDefaultValueAvailable()) {
                    $hasOptional = true;
                    $parameterSegments[] = "{{$paramName}?}";
                } else {
                    if ($hasOptional) {
                        continue;
                    }
                    $parameterSegments[] = "{{$paramName}}";
                }
            }

            $uri .= implode('/', $parameterSegments);
        }

        return $uri;
    }

    /**
     * Extract parameters that should appear in URL path
     *
     * Filters method parameters to include those appropriate for URL path segments.
     * Includes Eloquent Models (for route model binding), scalar types (int, string,
     * float, bool, mixed), and untyped parameters. Excludes complex types like arrays,
     * objects, Request instances, and other dependency injection candidates.
     *
     * @return Collection<int, ReflectionParameter> Collection of URL-appropriate parameters
     */
    protected function getUrlParameters(): Collection
    {
        return collect($this->method->getParameters())->filter(function (ReflectionParameter $parameter) {
            $type = $parameter->getType();

            if (!$type) {
                return true;
            }

            if (!$type instanceof ReflectionNamedType) {
                return false;
            }

            $typeName = $type->getName();

            if (is_a($typeName, Model::class, true)) {
                return true;
            }

            if (in_array($typeName, ['int', 'string', 'float', 'bool', 'mixed'])) {
                return true;
            }

            return false;
        });
    }

    /**
     * Add parameter constraint from Where attribute
     *
     * Stores regular expression constraint for route parameter. Overwrites existing
     * constraint if parameter already has one. Constraints are applied to Laravel
     * router during final route registration.
     *
     * @param Where $whereAttribute Where attribute containing parameter name and regex
     *
     * @return static Returns this instance for method chaining
     */
    public function addWhere(Where $whereAttribute): self
    {
        $this->wheres[$whereAttribute->param] = $whereAttribute->constraint;

        return $this;
    }

    /**
     * Add middleware to route action
     *
     * Merges middleware with existing middleware list, removing duplicates to prevent
     * double-execution. Accepts middleware as class names, aliases, or arrays. Middleware
     * is applied in order during request processing.
     *
     * @param array<class-string>|class-string $middleware Middleware to add
     *
     * @return static Returns this instance for method chaining
     */
    public function addMiddleware(array|string $middleware): self
    {
        $middleware = Arr::wrap($middleware);

        $allMiddleware = array_merge($middleware, $this->middleware);

        $this->middleware = array_unique($allMiddleware);

        return $this;
    }

    /**
     * Infer HTTP methods from controller method name
     *
     * Maps common Laravel resource controller method names to appropriate HTTP verbs
     * following RESTful conventions. Index, create, show, edit use GET; store uses POST;
     * update uses PUT and PATCH; destroy and delete use DELETE. All other methods default
     * to GET.
     *
     * @return array<int, string> Array of HTTP method verbs
     */
    protected function discoverHttpMethods(): array
    {
        return match ($this->method->name) {
            'index', 'create', 'show', 'edit' => ['GET'],
            'store' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'destroy', 'delete' => ['DELETE'],
            default => ['GET'],
        };
    }

    /**
     * Get list of common controller method names that map to root URI
     *
     * Methods in this list do not contribute their name to URI generation, mapping
     * directly to parent controller URI instead. Includes index, __invoke, get, show,
     * store, update, destroy, and delete.
     *
     * @return array<int, string> Array of method names
     */
    protected function commonControllerMethodNames(): array
    {
        return [
            'index',
            '__invoke',
            'get',
            'show',
            'store',
            'update',
            'destroy',
            'delete',
        ];
    }

    /**
     * Convert action array to Laravel route registration format
     *
     * Returns controller class name for invokable controllers (__invoke method), or
     * array tuple [controller, method] for standard controller methods. Format matches
     * Laravel's Route::get() action parameter expectations.
     *
     * @return string|array{string, string} Controller string or [controller, method] array
     */
    public function action(): string|array
    {
        return $this->action[1] === '__invoke'
            ? $this->action[0]
            : $this->action;
    }

    /**
     * Retrieve Route attribute instance if present on controller method
     *
     * Convenience method for accessing Route attribute specifically. Returns first Route
     * attribute instance or null if method has no Route attribute.
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
     * method. Searches for attribute class or any implementing subclass via IS_INSTANCEOF
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
        $attributes = $this->method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        if (!count($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
