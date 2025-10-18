<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hyper Signal Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how Hyper reads signals from frontend requests.
    | We keep this minimal and only include what we actually use.
    |
    */

    'route_discovery' => [
        'enabled' => false,  // Opt-in by default

        /*
         * Routes will be registered for all controllers found in
         * these directories.
         */
        'discover_controllers_in_directory' => [
            // app_path('Http/Controllers'),
        ],

        /*
         * Routes will be registered for all views found in these directories.
         * The key of an item will be used as the prefix of the uri.
         */
        'discover_views_in_directory' => [
            // 'docs' => resource_path('views/docs'),
        ],

        /*
         * After having discovered all controllers, these classes will manipulate the routes
         * before registering them to Laravel.
         */
        'pending_route_transformers' => [
            ...Dancycodes\Hyper\Routing\Config::defaultRouteTransformers(),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security behavior for locked signals, tampering detection,
    | and validation. These settings help protect server-controlled state
    | from client-side manipulation.
    |
    */

    'security' => [
        /*
         * Locked Signal Tampering Detection
         *
         * Controls how the application handles and logs attempts to tamper
         * with locked signals (signals ending with underscore suffix).
         */
        'locked_signals' => [
            /*
             * Log Tampering Violations
             *
             * When enabled, all detected signal tampering attempts are logged
             * with user context, IP address, and request details. Useful for
             * security auditing and intrusion detection.
             *
             * Set to false in development to reduce log noise during debugging.
             */
            'log_violations' => env('HYPER_LOG_SIGNAL_TAMPERING', true),
        ],
    ],
];
