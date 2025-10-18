<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\DoNotDiscover;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * DoNotDiscover Attribute Filter Transformer
 *
 * Filters controllers and methods marked with DoNotDiscover attribute from route
 * registration during discovery. Applies exclusion at both controller level (removing
 * entire controller and all methods) and method level (removing specific actions while
 * preserving other methods in the controller).
 *
 * This transformer executes early in the pipeline to prevent excluded routes from
 * undergoing further transformation processing.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\DoNotDiscover
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleDoNotDiscoverAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by filtering DoNotDiscover-marked routes
     *
     * Rejects pending routes where the controller class has DoNotDiscover attribute,
     * then rejects individual actions where the method has DoNotDiscover attribute.
     * Returns filtered collection with only discoverable routes remaining.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to filter
     *
     * @return Collection<int, PendingRoute> Filtered pending routes without excluded routes
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        return $pendingRoutes
            ->reject(fn (PendingRoute $pendingRoute): bool => (bool) $pendingRoute->getAttribute(DoNotDiscover::class))
            ->each(function (PendingRoute $pendingRoute) {
                /** @var Collection<int, PendingRouteAction> $actions */
                $actions = $pendingRoute
                    ->actions
                    ->reject(fn (PendingRouteAction $action): bool => (bool) $action->getAttribute(DoNotDiscover::class));

                $pendingRoute->actions = $actions;
            });
    }
}
