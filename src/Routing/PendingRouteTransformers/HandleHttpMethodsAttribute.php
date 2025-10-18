<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * HTTP Methods Attribute Handler Transformer
 *
 * Applies HTTP method constraints from Route attributes to override automatically
 * determined methods during discovery. Replaces default method inference (GET for index,
 * POST for store, etc.) with explicitly specified HTTP verbs from attribute configuration.
 *
 * Only method-level Route attributes affect HTTP methods. Controller-level Route
 * attributes do not cascade method constraints to child actions.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleHttpMethodsAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying HTTP method constraints from Route attributes
     *
     * Checks each action for Route attribute with methods property. When present and
     * non-empty, replaces the action's HTTP methods array with attribute-specified
     * methods, overriding convention-based method determination.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with explicit HTTP methods
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) {
                if (!$routeAttribute = $action->getRouteAttribute()) {
                    return;
                }

                if (!$routeAttribute instanceof Route) {
                    return;
                }

                if (!$httpMethods = $routeAttribute->methods) {
                    return;
                }

                $action->methods = $httpMethods;
            });
        });

        return $pendingRoutes;
    }
}
