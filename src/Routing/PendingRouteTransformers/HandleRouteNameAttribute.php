<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Route Name Attribute Handler Transformer
 *
 * Applies explicit route names from Route attributes to override automatically
 * generated route names during discovery. When name property is present in Route
 * attribute, replaces convention-based naming with developer-specified name.
 *
 * Only method-level Route attributes affect route names. Controller-level Route
 * attributes do not cascade name configuration to child actions.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleRouteNameAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying route names from Route attributes
     *
     * Checks each action for Route attribute with name property. When present and
     * non-null, replaces the action's route name with attribute-specified value,
     * overriding auto-generated dot-notation naming.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with explicit names
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

                if (!$name = $routeAttribute->name) {
                    return;
                }

                $action->name = $name;
            });
        });

        return $pendingRoutes;
    }
}
