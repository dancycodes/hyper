<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Controller URI Prepending Transformer
 *
 * Prepends controller-level URI segments to individual action URIs during route
 * discovery transformation. Combines the controller's base URI (derived from
 * filesystem location and naming) with method-specific URI segments to create
 * complete route paths.
 *
 * This transformer executes early in the pipeline to establish base URI structure
 * before other transformers apply attribute-based modifications and customizations.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 * @see \Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer
 */
class AddControllerUriToActions implements PendingRouteTransformer
{
    /**
     * Transform pending routes by prepending controller URIs to action URIs
     *
     * Iterates through all pending routes and their actions, prepending the
     * controller-level URI to each action's method-specific URI. If action has
     * existing URI segment, concatenates with forward slash separator.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with combined URIs
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) use ($pendingRoute) {
                $originalActionUri = $action->uri;

                $action->uri = $pendingRoute->uri;

                if ($originalActionUri) {
                    $action->uri .= "/{$originalActionUri}";
                }
            });
        });

        return $pendingRoutes;
    }
}
