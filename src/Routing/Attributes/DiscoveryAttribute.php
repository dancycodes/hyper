<?php

namespace Dancycodes\Hyper\Routing\Attributes;

/**
 * Route Discovery Attribute Marker Interface
 *
 * Identifies PHP 8 attributes that participate in the route discovery system.
 * All route-related attributes (DoNotDiscover, Route, Where, WithTrashed, etc.)
 * implement this interface to enable runtime identification during controller
 * and method reflection.
 *
 * The route discovery system uses ReflectionClass and ReflectionMethod to scan
 * controllers for attributes implementing this interface. This marker pattern
 * allows the discovery engine to filter relevant attributes from unrelated
 * PHP 8 attributes that may be present on the same classes or methods.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 */
interface DiscoveryAttribute {}
