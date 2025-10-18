<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Middleware Attribute Handler Transformer
 *
 * Applies middleware configuration from Route attributes to pending route actions
 * during discovery. Supports middleware cascading where controller-level middleware
 * is inherited by all methods and merged with method-level middleware declarations.
 *
 * Middleware from controller attributes applies first, followed by method attributes,
 * with duplicates removed to prevent double-execution of the same middleware.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleMiddlewareAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying middleware from Route attributes
     *
     * First applies controller-level middleware from Route attribute if present, then
     * applies method-level middleware which is merged with (not replaced by) controller
     * middleware. Duplicates are removed during merge to ensure each middleware executes
     * only once per request.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with merged middleware
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) use ($pendingRoute) {
                if ($pendingRouteAttribute = $pendingRoute->getRouteAttribute()) {
                    if ($pendingRouteAttribute instanceof Route) {
                        $action->addMiddleware($pendingRouteAttribute->middleware);
                    }
                }

                if ($actionRouteAttribute = $action->getRouteAttribute()) {
                    if ($actionRouteAttribute instanceof Route) {
                        $action->addMiddleware($actionRouteAttribute->middleware);
                    }
                }
            });
        });

        return $pendingRoutes;
    }
}
