<?php

namespace Dancycodes\Hyper\Http;

use Illuminate\Contracts\Support\Responsable;

/**
 * Hyper Redirect Builder - Full-Page Browser Redirects with Session Flash
 *
 * Provides fluent API for constructing full-page browser redirects that break out of reactive
 * mode and perform traditional navigation via window.location. Supports Laravel-style session
 * flash data for passing messages, errors, and input to destination pages.
 *
 * Unlike HyperResponse navigation methods which update content reactively, redirects generated
 * by this class trigger complete page reloads with browser history changes. Flash data is stored
 * in Laravel session before redirect execution and persists only for the next request.
 *
 * The redirect is implemented via JavaScript setTimeout to allow session write operations to
 * complete before navigation begins. This prevents race conditions where redirect occurs before
 * flash data is fully written to session storage.
 *
 * Implements Responsable interface allowing direct return from controllers. Automatically handles
 * session initialization, flash data storage, URL validation for security (same-domain checks),
 * and JavaScript generation for browser navigation.
 *
 * @see \Dancycodes\Hyper\Http\HyperResponse
 * @see \Illuminate\Contracts\Support\Responsable
 */
class HyperRedirect implements Responsable
{
    /** @var \Dancycodes\Hyper\Http\HyperResponse Parent response builder */
    protected HyperResponse $hyperResponse;

    /** @var string Target URL for redirect */
    protected string $url;

    /** @var array<string, mixed> Session flash data to persist for next request */
    protected array $flashData = [];

    /**
     * Initialize redirect builder with target URL and parent response
     *
     * @param string $url Destination URL for redirect
     * @param \Dancycodes\Hyper\Http\HyperResponse $hyperResponse Parent response builder instance
     */
    public function __construct(string $url, HyperResponse $hyperResponse)
    {
        $this->url = $url;
        $this->hyperResponse = $hyperResponse;
    }

    /**
     * Add data to session flash for next request
     *
     * Accepts either key-value pair or associative array of flash data. Flash data persists
     * in session only for the immediately following request, then is automatically removed.
     * Multiple calls accumulate flash data rather than replacing it.
     *
     * @param string|array<string, mixed> $key Flash data key or associative array
     * @param mixed $value Flash data value when $key is string, ignored when $key is array
     *
     * @return static Returns this instance for method chaining
     */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->flashData = array_merge($this->flashData, $key);
        } else {
            $this->flashData[$key] = $value;
        }

        return $this;
    }

    /**
     * Flash request input data to session for next request
     *
     * Stores form input data in session under '_old_input' key, typically used to repopulate
     * forms after validation failures. Uses current request input if no specific input provided.
     *
     * @param array<string, mixed>|null $input Input data to flash, or null for current request input
     *
     * @return static Returns this instance for method chaining
     */
    public function withInput(?array $input = null): self
    {
        $input = $input ?: request()->input();

        return $this->with('_old_input', $input);
    }

    /**
     * Flash validation errors to session for next request
     *
     * Stores error data in session under 'errors' key for display in destination page.
     * Accepts various error formats including MessageBag, Validator, or array.
     *
     * @param mixed $errors Error data in various supported formats
     *
     * @return static Returns this instance for method chaining
     */
    public function withErrors(mixed $errors): self
    {
        return $this->with('errors', $errors);
    }

    /**
     * Convert to HTTP response implementing Responsable interface
     *
     * Flashes accumulated session data, generates JavaScript for browser navigation, and returns
     * StreamedResponse. Session is started if inactive, flash data is written, then session is
     * saved before generating redirect script. JavaScript uses setTimeout to allow session write
     * completion before navigation begins.
     *
     * @param \Illuminate\Http\Request|null $request Laravel request instance or null for auto-detection
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse Streamed SSE response
     */
    public function toResponse($request = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Persist session flash data with dual-aging compensation
        if (!empty($this->flashData)) {
            if (!session()->isStarted()) {
                session()->start();
            }

            foreach ($this->flashData as $key => $value) {
                session()->flash((string) $key, $value);
            }

            // Persist flash data to storage, aging from _flash.new to _flash.old
            session()->save();

            // Compensate for dual session save by reflashing data back to _flash.new
            // StartSession middleware's terminate() will execute final save(), properly
            // aging flash data for consumption by the next request. Without reflash(),
            // terminate() would age data twice (_flash.old to deleted)
            session()->reflash();
        }

        $safeUrl = json_encode($this->url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Use 200ms delay to ensure session write completes before navigation
        // This prevents data loss under high load where writes may be delayed
        $script = "setTimeout(() => window.location = {$safeUrl}, 200)";

        /** @var \Symfony\Component\HttpFoundation\StreamedResponse $response */
        $response = $this->hyperResponse
            ->js($script, ['autoRemove' => true])
            ->toResponse($request);

        return $response;
    }

    /**
     * Redirect to previous URL with automatic same-domain validation
     *
     * Retrieves previous URL from Laravel's URL helper and validates it exists, differs from
     * current URL, and belongs to same domain for security. Falls back to provided URL if any
     * validation fails or if previous URL is external.
     *
     * @param string $fallback Fallback URL when no valid previous URL available
     *
     * @return static Returns this instance for method chaining
     */
    public function back(string $fallback = '/'): self
    {
        $previousUrl = url()->previous();

        if (
            !$previousUrl ||
            $previousUrl === request()->url() ||
            $previousUrl === request()->fullUrl()
        ) {
            $this->url = (string) url($fallback);
        } else {
            $previousDomain = parse_url((string) $previousUrl, PHP_URL_HOST);
            $currentDomain = parse_url((string) request()->url(), PHP_URL_HOST);

            if ($previousDomain === $currentDomain) {
                $this->url = $previousUrl;
            } else {
                $this->url = (string) url($fallback);
            }
        }

        return $this;
    }

    /**
     * Reload current page with optional query parameter preservation
     *
     * Redirects to current URL, optionally preserving existing query parameters and URL fragments.
     * Query preservation uses fullUrl(), while non-preservation uses base url() without parameters.
     * Fragment preservation attempts to extract from HTTP_REFERER when available.
     *
     * @param bool $preserveQuery Whether to maintain current query string parameters
     * @param bool $preserveFragment Whether to maintain URL fragment identifier
     *
     * @return static Returns this instance for method chaining
     */
    public function refresh(bool $preserveQuery = true, bool $preserveFragment = false): self
    {
        if ($preserveQuery) {
            $url = request()->fullUrl();

            if ($preserveFragment && isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
                $fragment = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_FRAGMENT);
                if ($fragment) {
                    $url .= '#' . $fragment;
                }
            }

            $this->url = $url;
        } else {
            $this->url = request()->url();
        }

        return $this;
    }

    /**
     * Redirect to application root URL
     *
     * Sets redirect destination to base application URL ('/'), typically the homepage
     * or main landing page configured in application routing.
     *
     * @return static Returns this instance for method chaining
     */
    public function home(): self
    {
        $this->url = url('/');

        return $this;
    }

    /**
     * Redirect to Laravel named route with parameters
     *
     * Generates URL from route name and parameters using Laravel's route() helper,
     * with option for relative or absolute URL generation. Throws exception if
     * specified route does not exist in application route definitions.
     *
     * @param string $routeName Laravel route name
     * @param array<string, mixed> $parameters Route parameter values
     * @param bool $absolute Whether to generate absolute URL with domain
     *
     * @throws \InvalidArgumentException When route name does not exist
     *
     * @return static Returns this instance for method chaining
     */
    public function route(string $routeName, array $parameters = [], bool $absolute = true): self
    {
        try {
            $this->url = route($routeName, $parameters, $absolute);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Route '{$routeName}' does not exist.");
        }

        return $this;
    }

    /**
     * Redirect to intended URL from authentication guard or default fallback
     *
     * Retrieves and removes intended URL from session (typically set by authentication
     * middleware), with same-domain validation for security. Falls back to provided default
     * URL if no intended URL exists or if intended URL is from external domain.
     *
     * @param string $default Fallback URL when no valid intended URL available
     *
     * @return static Returns this instance for method chaining
     */
    public function intended(string $default = '/'): self
    {
        $intendedUrl = session()->pull('url.intended', $default);

        if ($intendedUrl !== $default && is_string($intendedUrl)) {
            $intendedDomain = parse_url($intendedUrl, PHP_URL_HOST);
            $currentDomain = parse_url((string) request()->url(), PHP_URL_HOST);

            if ($intendedDomain !== $currentDomain) {
                $intendedUrl = $default;
            }
        }

        $urlString = is_string($intendedUrl) ? $intendedUrl : $default;
        $this->url = (string) url($urlString);

        return $this;
    }

    /**
     * Force immediate page reload with optional cache bypass
     *
     * Executes window.location.reload() in browser to refresh current page. When forceReload
     * is true, bypasses browser cache by passing true to reload() function. Flashes any pending
     * session data before reload to preserve messages and errors across refresh.
     *
     * @param bool $forceReload Whether to bypass browser cache and force server fetch
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse Streamed SSE response
     */
    public function forceReload(bool $forceReload = false): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Persist session flash data with dual-aging compensation
        if (!empty($this->flashData)) {
            if (!session()->isStarted()) {
                session()->start();
            }

            foreach ($this->flashData as $key => $value) {
                session()->flash((string) $key, $value);
            }

            // Persist flash data to storage, aging from _flash.new to _flash.old
            session()->save();

            // Compensate for dual session save by reflashing data back to _flash.new
            // StartSession middleware's terminate() will execute final save(), properly
            // aging flash data for consumption by the next request. Without reflash(),
            // terminate() would age data twice (_flash.old to deleted)
            session()->reflash();
        }

        $reloadParam = $forceReload ? 'true' : 'false';

        // Use 200ms delay to ensure session write completes before reload
        $script = "setTimeout(() => window.location.reload({$reloadParam}), 200)";

        /** @var \Symfony\Component\HttpFoundation\StreamedResponse $response */
        $response = $this->hyperResponse
            ->js($script, ['autoRemove' => true])
            ->toResponse();

        return $response;
    }

    /**
     * Redirect to previous URL with named route fallback
     *
     * Attempts to redirect to previous URL, falling back to specified named route if no
     * previous URL exists or if previous URL matches current URL. Combines back() and route()
     * functionality in single method for conditional navigation logic.
     *
     * @param string $routeName Fallback route name when previous URL unavailable
     * @param array<string, mixed> $routeParameters Parameters for fallback route
     *
     * @throws \InvalidArgumentException When fallback route name does not exist
     *
     * @return static Returns this instance for method chaining
     */
    public function backOr(string $routeName, array $routeParameters = []): self
    {
        $previousUrl = url()->previous();

        if (!$previousUrl || $previousUrl === request()->url()) {
            return $this->route($routeName, $routeParameters);
        }

        return $this->back();
    }
}
