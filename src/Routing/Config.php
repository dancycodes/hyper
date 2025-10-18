<?php

namespace Dancycodes\Hyper\Routing;

use Dancycodes\Hyper\Routing\PendingRouteTransformers\AddControllerUriToActions;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\AddDefaultRouteName;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleDomainAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleDoNotDiscoverAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleFullUriAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleHttpMethodsAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleMiddlewareAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleRouteNameAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleUriAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleUrisOfNestedControllers;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleWheresAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleWithTrashedAttribute;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\MoveRoutesStartingWithParametersLast;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\RejectDefaultControllerMethodRoutes;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\ValidateOptionalParameters;

class Config
{
    /**
     * @return array<class-string>
     */
    public static function defaultRouteTransformers(): array
    {
        return [
            RejectDefaultControllerMethodRoutes::class,
            HandleDoNotDiscoverAttribute::class,
            AddControllerUriToActions::class,
            HandleUrisOfNestedControllers::class,
            HandleRouteNameAttribute::class,
            HandleMiddlewareAttribute::class,
            HandleHttpMethodsAttribute::class,
            HandleUriAttribute::class,
            HandleFullUriAttribute::class,
            HandleWithTrashedAttribute::class,
            HandleWheresAttribute::class,
            AddDefaultRouteName::class,
            HandleDomainAttribute::class,
            ValidateOptionalParameters::class,
            MoveRoutesStartingWithParametersLast::class,
        ];
    }
}
