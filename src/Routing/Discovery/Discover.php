<?php

namespace Dancycodes\Hyper\Routing\Discovery;

/**
 * Route Discovery Factory
 *
 * Provides static factory methods for creating route discovery builders.
 * Supports automatic route registration from both controller classes using
 * PHP 8 attributes and Blade view files using filesystem conventions.
 *
 * This facade simplifies the route discovery configuration API by providing
 * dedicated builders for each discovery type (controllers vs views) with
 * their respective configuration options.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\DiscoverControllers
 * @see \Dancycodes\Hyper\Routing\Discovery\DiscoverViews
 */
class Discover
{
    /**
     * Create controller route discovery builder
     *
     * Returns a builder for configuring automatic controller route registration
     * based on PHP 8 attributes. The builder supports configuration of root
     * namespace and base path before scanning directories for controllers.
     *
     * @return DiscoverControllers Builder instance for controller discovery configuration
     */
    public static function controllers(): DiscoverControllers
    {
        return new DiscoverControllers;
    }

    /**
     * Create view route discovery builder
     *
     * Returns a builder for configuring automatic view route registration based
     * on Blade file locations and naming conventions. The builder automatically
     * registers routes for views found in specified directories.
     *
     * @return DiscoverViews Builder instance for view discovery configuration
     */
    public static function views(): DiscoverViews
    {
        return new DiscoverViews;
    }
}
