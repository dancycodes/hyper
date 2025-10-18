<?php

namespace Dancycodes\Hyper\Routing\Attributes;

use Attribute;

/**
 * Route Parameter Constraint Attribute
 *
 * Applies regular expression constraints to route parameters during route discovery.
 * Constraints validate parameter values before route matching occurs, preventing
 * invalid parameter formats from matching routes and improving routing performance
 * by failing fast on malformed URLs.
 *
 * Provides predefined constants for common patterns (alpha, numeric, alphanumeric, uuid)
 * to ensure consistency across route definitions. Custom regex patterns can be specified
 * directly in the constraint property for specialized validation requirements.
 *
 * When applied to controller classes, constraints cascade to all methods containing
 * matching route parameters. When applied to individual methods, constraints apply only
 * to that specific route definition.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 * @see \Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleWheresAttribute
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Where implements DiscoveryAttribute
{
    /** Regular expression pattern matching alphabetic characters only */
    public const alpha = '[a-zA-Z]+';

    /** Regular expression pattern matching numeric characters only */
    public const numeric = '[0-9]+';

    /** Regular expression pattern matching alphanumeric characters only */
    public const alphanumeric = '[a-zA-Z0-9]+';

    /** Regular expression pattern matching UUID format (8-4-4-4-12 hexadecimal) */
    public const uuid = '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}';

    /**
     * Initialize route parameter constraint attribute
     *
     * @param string $param Route parameter name to constrain
     * @param string $constraint Regular expression pattern for validation
     */
    public function __construct(
        public string $param,
        public string $constraint,
    ) {}
}
