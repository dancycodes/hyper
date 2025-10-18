<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Route Domain Attribute Handler Transformer
 *
 * Applies domain constraints from Route attributes to pending route actions during
 * discovery. Supports domain cascading where controller-level domain attributes
 * apply to all methods unless overridden by method-level attributes.
 *
 * Domain constraints enable multi-tenant routing where routes respond only to
 * specific subdomains or domain patterns with parameter binding support.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Route
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleDomainAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying domain constraints from Route attributes
     *
     * First applies controller-level domain from Route attribute if present, then
     * applies method-level domain which overrides controller-level configuration.
     * Method-level attributes take precedence over controller-level attributes.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with domain constraints
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) use ($pendingRoute) {
                if ($pendingRouteAttribute = $pendingRoute->getRouteAttribute()) {
                    if ($pendingRouteAttribute instanceof Route) {
                        $action->domain = $pendingRouteAttribute->domain;
                    }
                }

                if ($actionAttribute = $action->getRouteAttribute()) {
                    if ($actionAttribute instanceof Route) {
                        $action->domain = $actionAttribute->domain;
                    }
                }
            });
        });

        return $pendingRoutes;
    }
}
