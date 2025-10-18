<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\Attributes\Where;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Where Constraint Attribute Handler Transformer
 *
 * Applies regular expression constraints from Where attributes to route parameters
 * during discovery. Supports constraint cascading where controller-level Where
 * attributes apply to all methods containing matching parameters, merged with
 * method-level Where constraints.
 *
 * Multiple Where attributes can be applied to the same controller or method to
 * constrain different parameters with separate regular expressions.
 *
 * @see \Dancycodes\Hyper\Routing\Attributes\Where
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleWheresAttribute implements PendingRouteTransformer
{
    /**
     * Transform pending routes by applying parameter constraints from Where attributes
     *
     * First applies controller-level Where constraints if present, then applies
     * method-level Where constraints which are merged with (not replaced by) controller
     * constraints. When multiple Where attributes target the same parameter, later
     * constraints override earlier ones.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with parameter constraints
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions->each(function (PendingRouteAction $action) use ($pendingRoute) {
                if ($pendingRouteWhereAttribute = $pendingRoute->getAttribute(Where::class)) {
                    if ($pendingRouteWhereAttribute instanceof Where) {
                        $action->addWhere($pendingRouteWhereAttribute);
                    }
                }

                if ($actionWhereAttribute = $action->getAttribute(Where::class)) {
                    if ($actionWhereAttribute instanceof Where) {
                        $action->addWhere($actionWhereAttribute);
                    }
                }
            });
        });

        return $pendingRoutes;
    }
}
