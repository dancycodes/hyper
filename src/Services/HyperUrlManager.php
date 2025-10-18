<?php

namespace Dancycodes\Hyper\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use LogicException;

/**
 * URL Management for Navigate Responses
 *
 * Handles URL construction, validation, and browser history manipulation for reactive
 * navigation responses. Provides methods for building URLs from various formats including
 * route names, relative paths, and query parameter arrays, with validation for security
 * and reachability.
 *
 * Enforces same-origin policy for absolute URLs to prevent open redirect vulnerabilities.
 * Validates route existence when building from route names, ensures proper URL formatting
 * for relative and absolute paths.
 *
 * Generates JavaScript for browser History API manipulation with push and replace modes.
 * Enforces single URL operation per response to prevent conflicting history state changes
 * from multiple navigate directives.
 *
 * @see \Dancycodes\Hyper\Http\HyperResponse::navigate()
 * @see \Dancycodes\Hyper\Http\HyperResponse::url()
 */
class HyperUrlManager
{
    /**
     * Tracks whether URL has been set for current response
     */
    private bool $urlSet = false;

    /**
     * Initialize URL manager with request instance
     *
     * @param \Illuminate\Http\Request $request Current HTTP request
     */
    public function __construct(
        private Request $request
    ) {}

    /**
     * Build URL from various input formats
     *
     * Handles null for current request URL, array for query parameter merging with current
     * URL, and string for route names, relative paths, or absolute URLs. Delegates string
     * URL resolution to resolveStringUrl() for path and format handling.
     *
     * @param mixed $url URL input as null, string, or query parameter array
     *
     * @throws \InvalidArgumentException When URL format is not null, string, or array
     *
     * @return string Resolved absolute URL
     */
    public function buildUrl(mixed $url = null): string
    {
        if (is_null($url)) {
            return $this->request->url();
        }

        if (is_array($url)) {
            return $this->request->fullUrlWithQuery($url);
        }

        if (is_string($url)) {
            return $this->resolveStringUrl($url);
        }

        throw new InvalidArgumentException('URL must be null, string, or array of query parameters');
    }

    /**
     * Validate URL format and enforce same-origin policy
     *
     * Checks URL format validity using PHP filter_var for absolute URLs and custom
     * validation for relative paths. Enforces same-origin policy for absolute URLs
     * by validating host matches current request host, preventing open redirect attacks.
     *
     * @param string $url URL to validate
     *
     * @throws \InvalidArgumentException When URL format invalid or cross-origin URL detected
     */
    public function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !$this->isRelativePath($url)) {
            throw new InvalidArgumentException("Invalid URL format: {$url}");
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->validateSameOrigin($url);
        }
    }

    /**
     * Build and validate URL from route name with parameters
     *
     * Verifies route exists in application route collection, generates URL using Laravel
     * route helper with provided parameters, validates generated URL for security, and
     * returns resolved URL string.
     *
     * @param string $routeName Laravel route name
     * @param array<string, mixed> $params Route parameters for URL generation
     *
     * @throws \InvalidArgumentException When route does not exist or parameters invalid
     *
     * @return string Generated and validated route URL
     */
    public function buildRouteUrl(string $routeName, array $params = []): string
    {
        if (!Route::has($routeName)) {
            throw new InvalidArgumentException("Route '{$routeName}' does not exist");
        }

        try {
            $url = route($routeName, $params);
            $this->validateUrl($url);

            return $url;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid route parameters for '{$routeName}': " . $e->getMessage());
        }
    }

    /**
     * Generate JavaScript for browser History API manipulation
     *
     * Creates self-executing JavaScript function that updates browser history using either
     * pushState or replaceState based on mode parameter. Wraps operation in try-catch for
     * graceful degradation when History API unavailable. JSON-encodes URL and mode with
     * HTML escaping flags for safe script injection.
     *
     * @param string $url Target URL for history state
     * @param string $mode History operation mode: 'push' or 'replace'
     *
     * @return string Minified JavaScript for History API operation
     */
    public function generateHistoryScript(string $url, string $mode): string
    {
        $safeUrl = json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeMode = json_encode($mode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return '(function(){' .
            'try{' .
            "if({$safeMode}==='push'){" .
            "history.pushState(null,'',{$safeUrl});" .
            '}else{' .
            "history.replaceState(null,'',{$safeUrl});" .
            '}' .
            '}catch(e){' .
            "console.warn('History API failed:',e);" .
            '}' .
            '})();';
    }

    /**
     * Enforce single URL operation constraint per response
     *
     * Checks internal flag to prevent multiple URL manipulation calls in single response,
     * throws exception if URL already set. Sets flag after validation to block subsequent
     * calls. Prevents conflicting history operations from chained navigate methods.
     *
     * @throws \LogicException When URL operation already executed for current response
     */
    public function enforceUrlSingleUse(): void
    {
        if ($this->urlSet) {
            throw new LogicException('URL can only be set once per response. Use either url(), pushUrl(), replaceUrl(), or routeUrl() - not multiple.');
        }

        $this->urlSet = true;
    }

    /**
     * Reset URL operation flag for new response
     *
     * Resets internal flag allowing URL operations for new response instance. Used when
     * manager instance is reused across multiple response cycles.
     */
    public function reset(): void
    {
        $this->urlSet = false;
    }

    /**
     * Resolve string URL to absolute format
     *
     * Handles relative paths with leading slash by passing to Laravel url() helper, handles
     * full URLs by validating format and returning unchanged, handles paths without leading
     * slash by assuming relative and passing to url() helper.
     *
     * @param string $url URL string to resolve
     *
     * @return string Absolute URL
     */
    private function resolveStringUrl(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return url($url);
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return url($url);
    }

    /**
     * Determine if URL string represents relative path
     *
     * Checks for leading slash indicating site-relative path or absence of protocol
     * separator indicating path without scheme. Used to differentiate relative paths
     * from absolute URLs during validation.
     *
     * @param string $url URL string to check
     *
     * @return bool True if URL is relative path
     */
    private function isRelativePath(string $url): bool
    {
        return str_starts_with($url, '/') || !str_contains($url, '://');
    }

    /**
     * Validate URL host matches current request host for same-origin policy
     *
     * Parses URL to extract host component, compares with current request host to prevent
     * open redirect vulnerabilities. Throws exception when cross-origin URL detected.
     *
     * @param string $url Absolute URL to validate
     *
     * @throws \InvalidArgumentException When URL host differs from current request host
     */
    private function validateSameOrigin(string $url): void
    {
        $parsedUrl = parse_url($url);
        $currentHost = $this->request->getHost();

        if (isset($parsedUrl['host']) && $parsedUrl['host'] !== $currentHost) {
            throw new InvalidArgumentException("Cross-origin URLs not allowed. Got: {$parsedUrl['host']}, Expected: {$currentHost}");
        }
    }
}
