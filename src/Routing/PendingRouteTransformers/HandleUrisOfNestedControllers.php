<?php

namespace Dancycodes\Hyper\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionParameter;

/**
 * Nested Controller URI Composition Transformer
 *
 * Handles URI generation for nested controller hierarchies by composing parent resource
 * URIs into child controller routes. When controller namespaces form parent-child
 * relationships (e.g., UserController and User\PostController), child routes inherit
 * parent resource URIs complete with parameters.
 *
 * Deduplicates shared route parameters between parent and child, removes duplicate
 * parameter placeholders from child URIs, and replaces parent controller base URI
 * with parent action URI (including parameters) to create complete nested resource paths.
 *
 * Only processes child routes when parent has resourceful methods (show, edit, update,
 * destroy, delete) to ensure proper resource binding context.
 *
 * @see \Dancycodes\Hyper\Routing\RouteRegistrar
 */
class HandleUrisOfNestedControllers implements PendingRouteTransformer
{
    /**
     * Transform pending routes by composing nested controller URIs
     *
     * Iterates through pending routes to identify parent-child relationships based on
     * namespace hierarchy. For each parent with resourceful methods, finds child routes
     * and composes URIs by replacing parent base path with parent action path, removing
     * duplicate parameters shared between parent and child.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes Pending routes to transform
     *
     * @return Collection<int, PendingRoute> Transformed pending routes with nested URIs
     */
    public function transform(Collection $pendingRoutes): Collection
    {
        $pendingRoutes->each(function (PendingRoute $parentPendingRoute) use ($pendingRoutes) {
            $childrenNodes = $this->findChild($pendingRoutes, $parentPendingRoute);

            if (!$childrenNodes->count()) {
                return;
            }

            /** @var PendingRouteAction|null $parentAction */
            $parentAction = $parentPendingRoute->actions->first(function (PendingRouteAction $action) {
                return in_array($action->method->name, ['show', 'edit', 'update', 'destroy', 'delete']);
            });

            if (is_null($parentAction)) {
                return;
            }

            $childrenNodes->each(function (PendingRoute $childNode) use ($parentPendingRoute, $parentAction) {
                $childNode->actions->each(function (PendingRouteAction $action) use ($parentPendingRoute, $parentAction) {
                    /** @var Collection<int, ReflectionParameter> $paramsToRemove */
                    $paramsToRemove = $action->modelParameters()
                        ->filter(
                            fn (ReflectionParameter $parameter) => $parentAction
                                ->modelParameters()
                                ->contains(
                                    fn (ReflectionParameter $parentParameter) => $parentParameter->getName() === $parameter->getName()
                                )
                        );

                    /** @var array<int, string> $paramPlaceholders */
                    $paramPlaceholders = $paramsToRemove->map(fn (ReflectionParameter $parameter) => "{{$parameter->getName()}}")->all();

                    $result = Str::of($action->uri)
                        ->replace($paramPlaceholders, '')
                        ->replaceMatches('#/{2,}#', '/')
                        ->replace($parentPendingRoute->uri, $parentAction->uri);

                    $action->uri = $result;
                });
            });
        });

        return $pendingRoutes;
    }

    /**
     * Find child routes for parent route based on namespace hierarchy
     *
     * Identifies child routes whose namespace matches the parent's child namespace
     * pattern. Child namespace is constructed by appending parent controller's short
     * name (without 'Controller' suffix) to parent namespace.
     *
     * @param Collection<int, PendingRoute> $pendingRoutes All pending routes for searching
     * @param PendingRoute $parentRouteAction Parent route to find children for
     *
     * @return Collection<int, PendingRoute> Collection of child routes matching parent
     */
    protected function findChild(Collection $pendingRoutes, PendingRoute $parentRouteAction): Collection
    {
        $childNamespace = $parentRouteAction->childNamespace();

        return $pendingRoutes->filter(
            fn (PendingRoute $potentialChildRoute) => $potentialChildRoute->namespace() === $childNamespace
        );
    }
}
