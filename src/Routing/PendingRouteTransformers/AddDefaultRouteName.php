<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;

/**
 * Default Route Name Generation Transformer
 *
 * Generates convention-based route names for actions without explicit name attributes
 * during discovery. Constructs hierarchical dot-notation names from URI structure,
 * excluding parameter segments and optionally appending resourceful method names.
 *
 * Route names follow Laravel conventions: static URI segments joined with dots,
 * with special handling for common RESTful methods (show, store, edit, update, destroy, delete)
 * which append method name when it differs from the URI's final segment.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class AddDefaultRouteName implements PendingRouteTransformer
{
    /**
     * Transform pending routes by generating default route names
     *
     * Iterates through all pending routes and their actions, generating route names
     * for actions without existing names using URI-based convention. Skips actions
     * that already have explicit names from Route attributes.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with generated names
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $pendingRoute) {
            $pendingRoute->actions
                ->reject(fn (PendingRouteAction $action): bool => (bool) $action->name)
                ->each(fn (PendingRouteAction $action) => $action->name = $this->generateRouteName($action));
        });

        return $pendingRoutes;
    }

    /**
     * Generate route name from action URI and method
     *
     * Extracts static segments from URI (excluding parameters), then optionally appends
     * method name for common RESTful methods when method name differs from final URI
     * segment. Joins segments with dots to create hierarchical route name.
     *
     * @param PendingRouteAction $pendingRouteAction Route action to generate name for
     *
     * @return string Generated route name in dot notation
     */
    protected function generateRouteName(PendingRouteAction $pendingRouteAction): string
    {
        /** @var array<int, non-empty-string> $segments */
        $segments = collect(explode('/', $pendingRouteAction->uri))
            ->reject(fn (string $segment) => str_starts_with($segment, '{'))
            ->filter()
            ->all();

        $methodName = $this->discoverMethodRouteName($pendingRouteAction);

        if ($methodName !== null && $methodName !== end($segments)) {
            $segments[] = $methodName;
        }

        return implode('.', $segments);
    }

    /**
     * Determine route name suffix from method name for common RESTful methods
     *
     * Returns method name for standard Laravel resource methods (show, store, edit,
     * update, destroy, delete) to append to route name. Returns null for other methods
     * to use URI-only naming without method suffix.
     *
     * @param PendingRouteAction $pendingRouteAction Route action to analyze
     *
     * @return non-empty-string|null Method name for route suffix or null for non-resourceful methods
     */
    protected function discoverMethodRouteName(PendingRouteAction $pendingRouteAction): ?string
    {
        return match ($pendingRouteAction->method->name) {
            'show' => 'show',
            'store' => 'store',
            'edit' => 'edit',
            'update' => 'update',
            'destroy' => 'destroy',
            'delete' => 'delete',
            default => null,
        };
    }
}
