<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

/**
 * Base Controller Method Filter Transformer
 *
 * Filters inherited Laravel framework methods from route registration during discovery.
 * Prevents registration of base controller methods (middleware(), authorize(), etc.)
 * and application base controller methods that should not be accessible as routes.
 *
 * Checks method declaration class against configured rejection list including
 * Illuminate\Routing\Controller and App\Http\Controllers\Controller when present.
 * Special handling for middleware() method from HasMiddleware interface.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class RejectDefaultControllerMethodRoutes implements PendingRouteTransformer
{
    /**
     * Controller classes whose methods should be rejected from route registration
     *
     * @var array<int, class-string>
     */
    public array $rejectMethodsInClasses = [];

    /**
     * Initialize transformer with default rejection list
     *
     * Configures rejection list with Illuminate\Routing\Controller and optionally
     * App\Http\Controllers\Controller if it exists. PHPStan suppression required
     * as class_exists() check does not satisfy static analysis for class-string type.
     */
    public function __construct()
    {
        $this->rejectMethodsInClasses = [
            Controller::class,
        ];

        if (class_exists('App\Http\Controllers\Controller')) {
            /** @phpstan-ignore assign.propertyType */
            $this->rejectMethodsInClasses[] = 'App\Http\Controllers\Controller';
        }
    }

    /**
     * Transform pending routes by filtering base controller methods
     *
     * Rejects pending route actions where the declaring method class matches any
     * class in the rejection list. Additionally rejects middleware() method when
     * declaring class implements HasMiddleware interface (Laravel 11+ middleware
     * configuration pattern).
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to filter
     *
     * @return Collection<int, PendingRoute> Filtered pending routes without base methods
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        return $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions = $pendingRoute
                ->actions
                ->reject(function (PendingRouteAction $pendingRouteAction) {
                    if ($pendingRouteAction->method->name == 'middleware' && is_subclass_of($pendingRouteAction->method->class, 'Illuminate\\Routing\\Controllers\\HasMiddleware')) {
                        return true;
                    }

                    return in_array(
                        $pendingRouteAction->method->class,
                        $this->rejectMethodsInClasses
                    );
                });
        });
    }
}
