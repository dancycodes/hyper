<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Parameter-Starting Route Ordering Transformer
 *
 * Reorders pending routes to register routes beginning with parameters (e.g., {id})
 * after routes with static segments. This prevents parameter-based routes from
 * shadowing more specific static routes during Laravel's route matching process.
 *
 * Routes are sorted by specificity with static routes first, followed by routes
 * starting with parameters ordered by path depth. Deeper parameter routes register
 * before shallower ones to maximize matching precision.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class MoveRoutesStartingWithParametersLast implements PendingRouteTransformer
{
    /**
     * Transform pending routes by reordering parameter-starting routes to end
     *
     * Sorts pending routes collection by specificity score. Routes without actions
     * starting with parameters receive score 0 (highest priority). Routes with
     * parameter-starting actions receive scores based on inverse path depth,
     * ensuring deeper routes register before shallower routes.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to reorder
     *
     * @return Collection<int, PendingRoute> Reordered pending routes with reset array keys
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        return $pendingRoutes->sortBy(function (PendingRoute $pendingRoute) {
            $containsRouteStartingWithUri = $pendingRoute->actions->contains(function (PendingRouteAction $action) {
                return str_starts_with($action->uri, '{');
            });

            if (!$containsRouteStartingWithUri) {
                return 0;
            }

            return $pendingRoute->actions->max(function (PendingRouteAction $action) {
                if (!str_starts_with($action->uri, '{')) {
                    return PHP_INT_MAX;
                }

                return PHP_INT_MAX - count(explode('/', $action->uri));
            });
        })
            ->values();
    }
}
