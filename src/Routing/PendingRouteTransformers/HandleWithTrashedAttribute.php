<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Soft-Delete Model Binding Attribute Handler Transformer
 *
 * Applies withTrashed configuration from Route attributes to enable soft-deleted
 * Eloquent model resolution in route model binding. Supports cascading where
 * controller-level withTrashed applies to all methods unless overridden by
 * method-level attributes.
 *
 * When withTrashed is true, route model binding includes models with non-null
 * deleted_at timestamps. Only affects models using SoftDeletes trait.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\Attributes\WithTrashed
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleWithTrashedAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying withTrashed configuration from Route attributes
     *
     * First applies controller-level withTrashed from Route attribute if present, then
     * applies method-level withTrashed which overrides controller-level configuration.
     * Method-level attributes take precedence over controller-level attributes.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with withTrashed configuration
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        return $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) use ($pendingRoute) {
                if ($pendingRouteAttribute = $pendingRoute->getRouteAttribute()) {
                    if ($pendingRouteAttribute instanceof Route) {
                        $action->withTrashed = $pendingRouteAttribute->withTrashed;
                    }
                }

                if ($actionAttribute = $action->getRouteAttribute()) {
                    if ($actionAttribute instanceof Route) {
                        $action->withTrashed = $actionAttribute->withTrashed;
                    }
                }
            });
        });
    }
}
