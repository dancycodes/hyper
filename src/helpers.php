<?php

/**
 * Hyper Package Global Helper Functions
 *
 * Provides Laravel-style global helper functions for accessing Hyper services
 * throughout the application. These helpers follow Laravel's conventions for
 * global functions, mirroring patterns established by view(), request(), response(),
 * and other framework helpers.
 *
 * All helpers retrieve singleton instances from the service container, ensuring
 * consistent state throughout the request lifecycle. Functions are conditionally
 * defined to prevent conflicts with application-level helper definitions.
 *
 *
 * @see \Dancycodes\Hyper\Http\HyperSignal
 * @see \Dancycodes\Hyper\Http\HyperResponse
 * @see \Dancycodes\Hyper\Services\HyperFileStorage
 */
if (!function_exists('signals')) {
    /**
     * Access signal state management or retrieve a specific signal value
     *
     * When called without arguments, returns the HyperSignal singleton instance
     * for full access to signal management methods. When called with a key,
     * retrieves the value of a specific signal from the current request.
     *
     * The HyperSignal instance provides methods for accessing, validating, and
     * storing signals, as well as managing locked signals for security-sensitive
     * data that should not be modified by the client.
     *
     * @param string|null $key Optional signal key to retrieve
     * @param mixed $default Default value if signal key does not exist
     *
     * @return mixed HyperSignal instance when key is null, signal value otherwise
     *
     * @see \Dancycodes\Hyper\Http\HyperSignal
     */
    function signals(?string $key = null, mixed $default = null): mixed
    {
        $hyperSignal = app(\Dancycodes\Hyper\Http\HyperSignal::class);

        if (is_null($key)) {
            return $hyperSignal;
        }

        return $hyperSignal->get($key, $default);
    }
}

if (!function_exists('hyper')) {
    /**
     * Retrieve the request-scoped HyperResponse builder singleton
     *
     * Returns the same HyperResponse instance throughout a single request,
     * enabling event accumulation across multiple method calls. All events
     * added to the response are collected and sent together when the response
     * is converted to a Server-Sent Events stream.
     *
     * This singleton behavior allows building complex responses incrementally,
     * such as updating signals in one method and patching elements in another,
     * with all changes included in the final response.
     *
     * @return \Dancycodes\Hyper\Http\HyperResponse Singleton response builder instance
     *
     * @see \Dancycodes\Hyper\Http\HyperResponse
     */
    function hyper(): \Dancycodes\Hyper\Http\HyperResponse
    {
        return app('hyper.response');
    }
}

if (!function_exists('hyperStorage')) {
    /**
     * Access the HyperFileStorage service for base64 file operations
     *
     * Retrieves the singleton HyperFileStorage instance that handles storing,
     * validating, and converting base64-encoded files transmitted through Hyper
     * signals. Provides methods for storing files to Laravel's filesystem and
     * retrieving public URLs.
     *
     * This service seamlessly integrates base64 file uploads from Datastar's
     * client-side encoding with Laravel's storage system, handling MIME type
     * detection, file validation, and storage disk management.
     *
     * @return \Dancycodes\Hyper\Services\HyperFileStorage Singleton storage service instance
     *
     * @see \Dancycodes\Hyper\Services\HyperFileStorage
     * @see \Dancycodes\Hyper\Validation\HyperBase64Validator
     */
    function hyperStorage(): \Dancycodes\Hyper\Services\HyperFileStorage
    {
        return app(\Dancycodes\Hyper\Services\HyperFileStorage::class);
    }
}
