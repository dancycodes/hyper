<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Full URI Override Attribute Handler Transformer
 *
 * Applies fullUri property from Route attributes to completely override automatically
 * generated URIs during discovery. When fullUri is specified, bypasses all other URI
 * transformation logic including controller prepending, parameter generation, and
 * nested controller handling.
 *
 * This transformer executes late in the pipeline after other URI transformers,
 * providing absolute control over final route URI definition.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleFullUriAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying fullUri overrides from Route attributes
     *
     * Checks each action for Route attribute with fullUri property. When present,
     * replaces the action's entire URI with the specified fullUri value, bypassing
     * all previous URI construction logic.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with full URI overrides
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) {
                if (!$routeAttribute = $action->getRouteAttribute()) {
                    return;
                }

                /** @var Route $routeAttribute */
                if (!$routeAttributeFullUri = $routeAttribute->fullUri) {
                    return;
                }

                $action->uri = $routeAttributeFullUri;
            });
        });

        return $pendingRoutes;
    }
}
