<?php

namespace Dancycodes\Hyper\Routing\Attributes;

use Attribute;

/**
 * Route URI Prefix Attribute
 *
 * Applies a URI prefix to all routes within a controller during route discovery.
 * The prefix is prepended to both auto-generated URIs and explicitly defined URIs
 * on individual methods, enabling logical grouping of related routes under a
 * common path segment.
 *
 * Only applicable to controller classes, not individual methods. The prefix does
 * not affect route names, only the generated URI paths.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Prefix implements DiscoveryAttribute
{
    /**
     * Initialize route prefix attribute
     *
     * @param string $prefix URI segment to prepend to all controller routes
     */
    public function __construct(
        public string $prefix
    ) {}
}
