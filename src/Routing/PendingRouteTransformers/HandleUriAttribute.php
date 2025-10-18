<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * URI Attribute Handler Transformer
 *
 * Applies custom URI segments from Route attributes to replace automatically
 * generated method-based URI segments during discovery. The uri property replaces
 * only the method-specific portion of the URI while preserving controller and
 * parent path segments.
 *
 * Unlike fullUri which replaces the entire URI, this property modifies only the
 * final segment, enabling surgical customization of method URIs while maintaining
 * hierarchical path structure.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleUriAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying custom URI segments from Route attributes
     *
     * Checks each action for Route attribute with uri property. When present, extracts
     * the base URI path (everything before the last segment), then appends the custom
     * URI from the attribute, effectively replacing the method-specific segment while
     * preserving parent path components.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with custom URI segments
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) {
                $routeAttribute = $action->getRouteAttribute();

                if (!$routeAttribute instanceof Route) {
                    return;
                }

                if (!$routeAttribute->uri) {
                    return;
                }

                $baseUri = Str::beforeLast($action->uri, '/');
                $action->uri = $baseUri . '/' . $routeAttribute->uri;
            });
        });

        return $pendingRoutes;
    }
}
