<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Illuminate\Support\Collection;

/**
 * Pending Route Transformer Contract
 *
 * Defines transformation interface for modifying pending routes during the route
 * discovery process. Transformers are applied in pipeline fashion, with each
 * transformer receiving the collection of pending routes, applying modifications,
 * and returning the transformed collection.
 *
 * Transformers handle various aspects of route configuration including URI construction,
 * attribute processing, parameter validation, route ordering, and default filtering.
 * The transformation pipeline is configured via the hyper.route_discovery.pending_route_transformers
 * configuration array.
 *
 * Implementations must be stateless and idempotent to ensure predictable route
 * registration behavior across multiple discovery executions.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
interface PendingRouteTransformer
{
    /**
     * Transform collection of pending routes
     *
     * Receives collection of pending routes extracted from controller discovery and
     * applies transformation logic to modify URIs, attributes, parameters, or other
     * route properties. Returns the transformed collection which may contain fewer,
     * more, or reordered routes compared to input.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes collection
     */
    public function transform(Collection $pendingRoutes): Collection;
}
