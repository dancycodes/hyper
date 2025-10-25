<?php

namespace Dancycodes\Hyper\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Convert Redirects For Datastar Middleware
 *
 * Automatically intercepts standard Laravel redirect responses in Datastar requests and
 * converts them into Hyper-compatible SSE responses with client-side JavaScript redirects.
 * This middleware resolves session flash data persistence issues that occur when fetch API's
 * automatic redirect following consumes flash data before the intended page receives it.
 *
 * Session Flash Data Persistence Challenge:
 *
 * Standard Laravel redirects with Datastar requests create a double-navigation scenario:
 * - Initial fetch request receives 302 redirect response with flash data
 * - Fetch API automatically follows redirect, consuming flash data in process
 * - responseInterceptor detects redirect and triggers window.location navigation
 * - Final destination page receives empty flash data as it was already consumed
 *
 * Middleware Resolution Strategy:
 *
 * This middleware converts redirect responses (301, 302, 303, 307, 308) into Server-Sent
 * Events responses containing JavaScript that executes client-side navigation via
 * window.location. This prevents fetch's automatic redirect following, ensuring flash data
 * persists correctly for the destination page. The middleware uses session()->reflash() to
 * preserve flash data through Laravel's session aging mechanism, accounting for the dual
 * save() calls from both response generation and StartSession middleware's terminate method.
 *
 * The middleware registers globally via HyperServiceProvider, activating only for requests
 * with the Datastar-Request header and skipping responses already processed by HyperRedirect.
 * No application-level configuration is required for operation.
 *
 * @see \Dancycodes\Hyper\Http\HyperRedirect
 * @see \Dancycodes\Hyper\HyperServiceProvider
 */
class ConvertRedirectsForDatastar
{
    /**
     * Handle an incoming request
     *
     * Processes responses from the application pipeline, intercepting redirect responses
     * for Datastar requests and converting them to SSE responses with JavaScript navigation.
     * Non-Datastar requests, non-redirect responses, and already-processed Hyper responses
     * pass through unchanged.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Pass through non-Datastar requests unchanged
        if (!$request->hasHeader('Datastar-Request')) {
            return $response;
        }

        // Skip responses already processed by HyperRedirect to prevent double-conversion
        if ($response->headers->has('X-Hyper-Response')) {
            return $response;
        }

        // Pass through non-redirect responses unchanged
        if (!$this->isRedirect($response)) {
            return $response;
        }

        // Convert redirect to Hyper SSE response with JavaScript navigation
        return $this->convertToHyperRedirect($response, $request);
    }

    /**
     * Determine if response represents a redirect
     *
     * Checks for RedirectResponse instance or HTTP status codes indicating redirects
     * (201 Created with Location, 301 Moved Permanently, 302 Found, 303 See Other,
     * 307 Temporary Redirect, 308 Permanent Redirect).
     *
     * @return bool True if response is a redirect, false otherwise
     */
    protected function isRedirect(Response $response): bool
    {
        return $response instanceof RedirectResponse ||
               in_array($response->getStatusCode(), [201, 301, 302, 303, 307, 308]);
    }

    /**
     * Convert redirect response to Hyper SSE response with JavaScript navigation
     *
     * Transforms a standard HTTP redirect response into a Server-Sent Events response
     * containing JavaScript that performs client-side navigation. This approach prevents
     * fetch API's automatic redirect following, ensuring session flash data persists
     * correctly through the navigation cycle.
     *
     * The method generates a JavaScript snippet using setTimeout with 200ms delay (matching
     * HyperRedirect timing) to allow session persistence operations to complete before
     * navigation executes. Flash data preservation is handled via session()->reflash() to
     * account for Laravel's dual session save mechanism during request termination.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response Original redirect response
     * @param \Illuminate\Http\Request $request Current request instance
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse SSE response with navigation script
     */
    protected function convertToHyperRedirect(Response $response, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $redirectUrl = $this->getRedirectUrl($response);

        // Build SSE response with JavaScript navigation script
        $hyperResponse = app('hyper.response');
        $safeUrl = json_encode($redirectUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $script = "setTimeout(() => window.location.href = {$safeUrl}, 200)";

        /** @var \Symfony\Component\HttpFoundation\StreamedResponse $hyperStreamedResponse */
        $hyperStreamedResponse = $hyperResponse
            ->js($script, ['autoRemove' => true])
            ->toResponse($request);

        // Preserve session flash data and attach session cookie to new response
        $this->preserveFlashData($hyperStreamedResponse);

        // Transfer cookies from original response to maintain state (CSRF tokens, etc.)
        foreach ($response->headers->getCookies() as $cookie) {
            $hyperStreamedResponse->headers->setCookie($cookie);
        }

        return $hyperStreamedResponse;
    }

    /**
     * Extract target URL from redirect response
     *
     * Attempts to retrieve redirect destination URL from Location header or
     * RedirectResponse::getTargetUrl(). Falls back to current request URL if
     * redirect target cannot be determined from response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response Redirect response to process
     *
     * @return string Target URL for navigation
     */
    protected function getRedirectUrl(Response $response): string
    {
        // Prefer Location header as primary source for redirect URL
        $location = $response->headers->get('Location');
        if ($location) {
            return $location;
        }

        // Extract from RedirectResponse instance if available
        if ($response instanceof RedirectResponse) {
            return $response->getTargetUrl();
        }

        // Fallback to current URL when redirect target cannot be determined
        return request()->url();
    }

    /**
     * Preserve session flash data through navigation cycle
     *
     * Manages session flash data persistence by utilizing Laravel's reflash() method to
     * counteract the dual aging mechanism inherent in Datastar redirect handling. When
     * redirect()->with() executes, it ages flash data from _flash.new to _flash.old.
     * Without intervention, StartSession middleware's terminate() method would age the
     * data a second time (_flash.old to deleted), rendering it unavailable to the
     * destination page.
     *
     * The reflash() call moves flash data back to _flash.new in memory, allowing
     * terminate()'s save() operation to properly age it for consumption by the next
     * request. This method also manually creates and attaches the session cookie to
     * the new SSE response, as the response object bypasses Laravel's standard cookie
     * attachment mechanisms.
     *
     * @param \Symfony\Component\HttpFoundation\StreamedResponse $response SSE response requiring session cookie
     */
    protected function preserveFlashData(\Symfony\Component\HttpFoundation\StreamedResponse $response): void
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        // Move flash data from _flash.old back to _flash.new to prevent double-aging
        // This ensures terminate() method properly ages data for next request availability
        session()->reflash();

        // Manually attach session cookie as SSE response bypasses standard cookie handling
        // StartSession middleware's terminate() will persist session data to storage
        $sessionCookie = $this->createSessionCookie();
        $response->headers->setCookie($sessionCookie);
    }

    /**
     * Create session cookie from current session state and configuration
     *
     * Constructs a session cookie using Laravel's cookie() helper with configuration
     * values from config/session.php. This manual cookie creation is necessary because
     * the SSE response object doesn't participate in Laravel's standard session cookie
     * attachment process that occurs in StartSession middleware.
     *
     * @return \Symfony\Component\HttpFoundation\Cookie Session cookie with current session ID
     */
    protected function createSessionCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        /** @var array<string, mixed> $config */
        $config = config('session', []);

        $name = is_string($config['cookie'] ?? null) && $config['cookie'] !== '' ? $config['cookie'] : 'laravel_session';
        $lifetime = is_int($config['lifetime'] ?? null) ? $config['lifetime'] : 120;
        $path = is_string($config['path'] ?? null) ? $config['path'] : '/';
        $domain = is_string($config['domain'] ?? null) ? $config['domain'] : null;
        $secure = is_bool($config['secure'] ?? null) ? $config['secure'] : false;
        $httpOnly = is_bool($config['http_only'] ?? null) ? $config['http_only'] : true;
        $sameSite = is_string($config['same_site'] ?? null) ? $config['same_site'] : 'lax';

        return cookie(
            $name,
            session()->getId(),
            $lifetime,
            $path,
            $domain,
            $secure,
            $httpOnly,
            false,
            $sameSite
        );
    }
}
