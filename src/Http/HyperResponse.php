<?php

namespace Dancycodes\Hyper\Http;

use Closure;
use Dancycodes\Hyper\View\Fragment\BladeFragment;
use Illuminate\Contracts\Support\Responsable;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HyperResponse - Fluent Response Builder for Reactive HTTP Responses
 *
 * Provides a fluent API for constructing Server-Sent Events (SSE) responses compatible
 * with the Datastar reactive framework. This class implements the Responsable interface,
 * allowing direct return from Laravel route handlers and controllers.
 *
 * The response builder operates in two distinct modes:
 * - Normal Mode: Accumulates events in memory and sends all events when converted to a response
 * - Streaming Mode: Sends events immediately as they are added, enabling long-running operations
 *
 * Core capabilities include:
 * - DOM manipulation through element patching (append, prepend, replace, remove, etc.)
 * - Reactive signal updates synchronized between server and client
 * - JavaScript execution in the browser context
 * - Fragment-based partial view rendering for granular UI updates
 * - Browser history manipulation (pushState/replaceState)
 * - Client-side navigation with query parameter merging strategies
 * - Custom event dispatching for component communication
 * - Full-page redirects with session flash data support
 *
 * All reactive methods automatically detect Hyper requests using the Datastar-Request
 * header and gracefully degrade for standard HTTP requests when a web fallback is provided.
 *
 * @see \Illuminate\Contracts\Support\Responsable
 * @see \Dancycodes\Hyper\Http\HyperSignal
 * @see \Dancycodes\Hyper\Http\HyperRedirect
 */
class HyperResponse implements Responsable
{
    /** @var array<int, string> */
    protected array $events = [];

    protected bool $streamingMode = false;

    protected ?Closure $streamCallback = null;

    /** @var mixed */
    protected $webResponse = null;

    /**
     * Retrieve Server-Sent Events HTTP headers for streaming responses
     *
     * Returns standardized headers required for SSE communication compatible with
     * the Datastar protocol. Includes cache control, content type, buffering directives,
     * and protocol-specific connection handling for HTTP/1.1.
     *
     * @return array<string, string> Associative array of header names and values
     */
    public static function headers(): array
    {
        $headers = [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'X-Hyper-Response' => 'true',
        ];

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        if (is_string($protocol) && $protocol === 'HTTP/1.1') {
            $headers['Connection'] = 'keep-alive';
        }

        return $headers;
    }

    /**
     * Render a complete Blade view and patch it into the DOM
     *
     * Compiles the specified Blade view with provided data and sends the rendered HTML
     * as a DOM patch event. Only processes for Hyper requests unless $web is true.
     *
     * @param string $view Blade view name (dot notation supported)
     * @param array<string, mixed> $data Variables to pass to the view template
     * @param array<string, mixed> $options DOM patching options (selector, mode, useViewTransition)
     * @param bool $web Whether to set this view as the fallback for non-Hyper requests
     *
     * @return static Returns this instance for method chaining
     */
    public function view(string $view, array $data = [], array $options = [], bool $web = false): self
    {

        if ($web) {
            /** @phpstan-ignore argument.type (view-string is Laravel's type hint) */
            $this->web(view($view, $data));
        }

        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        /** @phpstan-ignore argument.type (view-string is Laravel's type hint) */
        $html = view($view, $data)->render();

        return $this->patchElements($html, $options);
    }

    /**
     * Render a specific Blade fragment and patch it into the DOM
     *
     * Extracts and renders only the specified fragment from the Blade view, avoiding
     * full view compilation. Fragments are defined using @fragment/@endfragment directives.
     *
     * @param string $view Blade view containing the target fragment
     * @param string $fragment Name of the fragment to render
     * @param array<string, mixed> $data Variables to pass to the fragment
     * @param array<string, mixed> $options DOM patching options (selector, mode, useViewTransition)
     *
     * @return static Returns this instance for method chaining
     */
    public function fragment(string $view, string $fragment, array $data = [], array $options = []): self
    {
        return $this->patchFragment($view, $fragment, $data, $options);
    }

    /**
     * Render and patch multiple fragments from various views
     *
     * Processes multiple fragment specifications in a single method call, where each
     * fragment configuration specifies the view, fragment name, data, and patch options.
     *
     * @param array<int, array{view: string, fragment: string, data?: array<string, mixed>, options?: array<string, mixed>}> $fragments Array of fragment configurations
     *
     * @return static Returns this instance for method chaining
     */
    public function fragments(array $fragments): self
    {
        return $this->patchFragments($fragments);
    }

    /**
     * Patch raw HTML content into the DOM
     *
     * Sends arbitrary HTML content as a DOM patch event without view compilation.
     * Useful for dynamically generated markup or client-provided HTML strings.
     *
     * @param string $html Raw HTML markup to patch
     * @param array<string, mixed> $options DOM patching options (selector, mode, useViewTransition)
     *
     * @return static Returns this instance for method chaining
     */
    public function html(string $html, array $options = []): self
    {
        return $this->patchElements($html, $options);
    }

    /**
     * Alias for view() method using component terminology
     *
     * Provides alternative method name for developers who prefer component naming
     * conventions. Functionally identical to the view() method.
     *
     * @param string $view Blade view name (dot notation supported)
     * @param array<string, mixed> $data Variables to pass to the view template
     * @param array<string, mixed> $options DOM patching options (selector, mode, useViewTransition)
     * @param bool $web Whether to set this view as the fallback for non-Hyper requests
     *
     * @return static Returns this instance for method chaining
     */
    public function component(string $view, array $data = [], array $options = [], bool $web = false): self
    {
        return $this->view($view, $data, $options, $web);
    }

    /**
     * Update reactive signals in the client-side signal store
     *
     * Sends signal updates to synchronize server state with the client. Accepts either
     * a single key-value pair or an associative array of multiple signals.
     *
     * @param string|array<string, mixed> $key Signal name or associative array of signals
     * @param mixed $value Signal value when $key is a string, ignored when $key is array
     *
     * @return static Returns this instance for method chaining
     */
    public function signals(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            return $this->updateSignals($key);
        }

        return $this->updateSignals([$key => $value]);
    }

    /**
     * Remove signals from the client-side signal store
     *
     * Deletes specified signals or all signals if no specific names are provided.
     * Signal deletion is performed by sending null values to the client per Datastar protocol.
     * For locked signals (names ending with underscore), also removes them from server-side
     * session storage to maintain consistency.
     *
     * By default, both normal and locked signals are forgotten. Set $includeLocked to false
     * to only forget normal signals while preserving locked signals in both client store
     * and server session.
     *
     * @param string|array<int, string>|null $signals Signal name(s) to delete, or null to delete all
     * @param bool $includeLocked Whether to include locked signals in deletion (default: true)
     *
     * @return static Returns this instance for method chaining
     */
    public function forget(string|array|null $signals = null, bool $includeLocked = true): self
    {
        if (is_null($signals)) {
            /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
            $hyperSignal = signals();
            $signals = array_keys($hyperSignal->all());
        }

        // Filter out locked signals if requested
        $signalNames = $this->parseSignalNames($signals);
        if (!$includeLocked) {
            $signalNames = array_filter($signalNames, fn ($name) => !str_ends_with($name, '_'));
        }

        // Clean up locked signals from server-side session storage
        if ($includeLocked) {
            /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
            $hyperSignal = signals();
            foreach ($signalNames as $signalName) {
                if (str_ends_with($signalName, '_')) {
                    $hyperSignal->clearLockedSignal($signalName);
                }
            }
        }

        return $this->forgetSignals($signalNames);
    }

    /**
     * Execute JavaScript code in the browser context
     *
     * Injects and executes JavaScript code by creating a script element in the DOM.
     * The script element is automatically removed after execution unless configured otherwise.
     *
     * @param string $script JavaScript code to execute
     * @param array<string, mixed> $options Script element options (attributes, autoRemove)
     *
     * @return static Returns this instance for method chaining
     */
    public function js(string $script, array $options = []): self
    {
        return $this->executeScript($script, $options);
    }

    /**
     * Alias for js() method using script terminology
     *
     * Provides alternative method name for JavaScript execution. Functionally
     * identical to the js() method.
     *
     * @param string $script JavaScript code to execute
     * @param array<string, mixed> $options Script element options (attributes, autoRemove)
     *
     * @return static Returns this instance for method chaining
     */
    public function script(string $script, array $options = []): self
    {
        return $this->js($script, $options);
    }

    /**
     * Dispatch a custom browser event for inter-component communication
     *
     * Creates and dispatches a CustomEvent either globally on the window object or
     * targeted to specific DOM elements via CSS selectors. Event data is accessible
     * through the event.detail property. Supports standard CustomEvent options including
     * bubbling, cancelable, and composed properties.
     *
     * @param string $eventName Name of the CustomEvent to dispatch
     * @param array<string, mixed> $data Event payload accessible via event.detail
     * @param array<string, mixed> $options Dispatch configuration (selector, window, bubbles, cancelable, composed)
     *
     * @throws \InvalidArgumentException When event name is empty
     *
     * @return static Returns this instance for method chaining
     */
    public function dispatch(string $eventName, array $data = [], array $options = []): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        // Validate event name
        if (empty($eventName)) {
            throw new \InvalidArgumentException('Event name cannot be empty');
        }

        // Extract options with defaults
        $selector = $options['selector'] ?? null;
        $window = $options['window'] ?? (!$selector); // Default to window if no selector
        $bubbles = $options['bubbles'] ?? true;
        $cancelable = $options['cancelable'] ?? true;
        $composed = $options['composed'] ?? true;

        // Safe JSON encoding
        $safeEventName = json_encode($eventName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeData = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeBubbles = $bubbles ? 'true' : 'false';
        $safeCancelable = $cancelable ? 'true' : 'false';
        $safeComposed = $composed ? 'true' : 'false';

        // Generate dispatch script
        if ($selector) {
            // Targeted dispatch to CSS selector(s)
            $safeSelector = json_encode($selector, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

            $script = "
                (function() {
                    const targets = document.querySelectorAll({$safeSelector});
                    const eventName = {$safeEventName};
                    const data = {$safeData};

                    if (targets.length === 0) {
                        console.warn('[Hyper Dispatch] No elements found for selector:', {$safeSelector});
                        return;
                    }

                    targets.forEach(target => {
                        target.dispatchEvent(new CustomEvent(eventName, {
                            detail: data,
                            bubbles: {$safeBubbles},
                            cancelable: {$safeCancelable},
                            composed: {$safeComposed}
                        }));
                    });
                })();
            ";
        } elseif ($window) {
            // Global dispatch to window
            $script = "
                window.dispatchEvent(new CustomEvent({$safeEventName}, {
                    detail: {$safeData},
                    bubbles: {$safeBubbles},
                    cancelable: {$safeCancelable},
                    composed: {$safeComposed}
                }));
            ";
        } else {
            // Fallback to body dispatch
            $script = "
                document.body.dispatchEvent(new CustomEvent({$safeEventName}, {
                    detail: {$safeData},
                    bubbles: {$safeBubbles},
                    cancelable: {$safeCancelable},
                    composed: {$safeComposed}
                }));
            ";
        }

        return $this->executeScript($script, ['autoRemove' => true]);
    }

    /**
     * Remove matched elements from the DOM
     *
     * Deletes all elements matching the specified CSS selector from the document.
     *
     * @param string $selector CSS selector targeting elements to remove
     *
     * @return static Returns this instance for method chaining
     */
    public function remove(string $selector): self
    {
        return $this->removeElements($selector);
    }

    /**
     * Append HTML content as the last child of matched elements
     *
     * Inserts the provided HTML inside targeted elements, after their existing children.
     * Corresponds to Datastar patch mode 'append'.
     *
     * @param string $selector CSS selector targeting parent elements
     * @param string $html HTML markup to append
     *
     * @return static Returns this instance for method chaining
     */
    public function append(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'append',
        ]);
    }

    /**
     * Prepend HTML content as the first child of matched elements
     *
     * Inserts the provided HTML inside targeted elements, before their existing children.
     * Corresponds to Datastar patch mode 'prepend'.
     *
     * @param string $selector CSS selector targeting parent elements
     * @param string $html HTML markup to prepend
     *
     * @return static Returns this instance for method chaining
     */
    public function prepend(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'prepend',
        ]);
    }

    /**
     * Replace matched elements entirely with new HTML
     *
     * Substitutes all elements matching the selector with the provided HTML markup.
     * Corresponds to Datastar patch mode 'replace'.
     *
     * @param string $selector CSS selector targeting elements to replace
     * @param string $html Replacement HTML markup
     *
     * @return static Returns this instance for method chaining
     */
    public function replace(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'replace',
        ]);
    }

    /**
     * Insert HTML content immediately before matched elements
     *
     * Places the provided HTML as a sibling before each targeted element.
     * Corresponds to Datastar patch mode 'before'.
     *
     * @param string $selector CSS selector targeting reference elements
     * @param string $html HTML markup to insert
     *
     * @return static Returns this instance for method chaining
     */
    public function before(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'before',
        ]);
    }

    /**
     * Insert HTML content immediately after matched elements
     *
     * Places the provided HTML as a sibling after each targeted element.
     * Corresponds to Datastar patch mode 'after'.
     *
     * @param string $selector CSS selector targeting reference elements
     * @param string $html HTML markup to insert
     *
     * @return static Returns this instance for method chaining
     */
    public function after(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'after',
        ]);
    }

    /**
     * Replace the inner HTML of matched elements
     *
     * Replaces all children of targeted elements with the provided HTML markup, preserving
     * the elements themselves. Corresponds to Datastar patch mode 'inner'.
     *
     * @param string $selector CSS selector targeting container elements
     * @param string $html HTML markup for new inner content
     *
     * @return static Returns this instance for method chaining
     */
    public function inner(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'inner',
        ]);
    }

    /**
     * Replace the outer HTML of matched elements
     *
     * Replaces targeted elements entirely including the elements themselves and their children.
     * Corresponds to Datastar patch mode 'outer'.
     *
     * @param string $selector CSS selector targeting elements to replace
     * @param string $html HTML markup for complete replacement
     *
     * @return static Returns this instance for method chaining
     */
    public function outer(string $selector, string $html): self
    {
        return $this->patchElements($html, [
            'selector' => $selector,
            'mode' => 'outer',
        ]);
    }

    /**
     * Patch HTML elements into the DOM using Datastar protocol
     *
     * Constructs and handles a datastar-patch-elements event with the provided HTML content
     * and patching options. Behavior depends on response mode: accumulates in normal mode,
     * sends immediately in streaming mode.
     *
     * @param string $elements HTML markup to patch
     * @param array<string, mixed> $options Patching configuration (selector, mode, useViewTransition)
     *
     * @return static Returns this instance for method chaining
     */
    protected function patchElements(string $elements, array $options = []): self
    {
        $dataLines = $this->buildElementsEvent($elements, $options);
        $this->handleEvent('datastar-patch-elements', $dataLines);

        return $this;
    }

    /**
     * Update reactive signals with locked signal support and session persistence
     *
     * Synchronizes server-side signal state with the client by sending signal updates
     * via SSE. Handles locked signal storage, deletion via null values per Datastar
     * protocol, and automatic conversion of complex data types using the signals directive.
     * Only processes for Hyper requests to avoid unnecessary computation.
     *
     * @param array<string, mixed> $signals Associative array of signal names and values
     * @param array<string, mixed> $options Signal update options (onlyIfMissing)
     *
     * @return static Returns this instance for method chaining
     */
    protected function updateSignals(array $signals, array $options = []): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        $this->handleSignalDeletions($signals);

        $directive = app('hyper.signals.directive');
        $convertedSignals = $directive->convertSignalBatch($signals);

        $this->handleLockedSignalUpdates($convertedSignals);

        $filteredSignals = array_filter($convertedSignals, function ($value, $key) {
            return !($value === null && str_ends_with($key, '_'));
        }, ARRAY_FILTER_USE_BOTH);

        $dataLines = $this->buildSignalsEvent($filteredSignals, $options);
        $this->handleEvent('datastar-patch-signals', $dataLines);

        return $this;
    }

    /**
     * Handle locked signal deletions using null value protocol
     *
     * Processes locked signals marked for deletion (indicated by null values and trailing
     * underscore) by removing them from server-side session storage. Regular signals with
     * null values are passed to the client for deletion in the signal store.
     *
     * @param array<string, mixed> $signals Signal array potentially containing null values
     */
    protected function handleSignalDeletions(array $signals): void
    {
        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();

        foreach ($signals as $signalName => $value) {
            if ($value === null && str_ends_with((string) $signalName, '_')) {
                $hyperSignal->deleteSignal($signalName);
            }
        }
    }

    /**
     * Handle locked signal updates with session storage persistence
     *
     * Stores locked signals (identified by trailing underscore) in server-side session
     * for subsequent request validation. The HyperSignal class automatically handles
     * first-call versus subsequent-call logic through its internal detection mechanism.
     *
     * @param array<string, mixed> $signals Converted signal array
     */
    protected function handleLockedSignalUpdates(array $signals): void
    {
        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();
        $lockedSignals = array_filter($signals, function ($value, $key) {
            return str_ends_with((string) $key, '_') && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($lockedSignals)) {
            $hyperSignal->storeLockedSignals($signals);
        }
    }

    /**
     * Execute JavaScript code in the browser context
     *
     * Constructs and handles a datastar-patch-elements event containing a script element.
     * Behavior depends on response mode: accumulates in normal mode, sends immediately
     * in streaming mode.
     *
     * @param string $script JavaScript code to execute
     * @param array<string, mixed> $options Script configuration (attributes, autoRemove)
     *
     * @return static Returns this instance for method chaining
     */
    protected function executeScript(string $script, array $options = []): self
    {
        $dataLines = $this->buildScriptEvent($script, $options);
        $this->handleEvent('datastar-patch-elements', $dataLines);

        return $this;
    }

    /**
     * Remove matched elements from the DOM
     *
     * Constructs and handles a datastar-patch-elements event with mode 'remove'.
     * Behavior depends on response mode: accumulates in normal mode, sends immediately
     * in streaming mode.
     *
     * @param string $selector CSS selector targeting elements to remove
     * @param array<string, mixed> $options Removal configuration (useViewTransition)
     *
     * @return static Returns this instance for method chaining
     */
    protected function removeElements(string $selector, array $options = []): self
    {
        $options['selector'] = $selector;
        $options['mode'] = 'remove';
        $dataLines = $this->buildRemovalEvent($options);
        $this->handleEvent('datastar-patch-elements', $dataLines);

        return $this;
    }

    /**
     * Create a fluent redirect builder with session flash support
     *
     * Returns a HyperRedirect instance that provides methods for full-page browser redirects
     * with Laravel session flash data. Redirects perform JavaScript-based navigation using
     * window.location assignments rather than reactive signal updates.
     *
     * @param string $url Target URL for the redirect
     *
     * @return \Dancycodes\Hyper\Http\HyperRedirect Redirect builder instance
     */
    public function redirect(string $url): HyperRedirect
    {
        return new HyperRedirect($url, $this);
    }

    /**
     * Update browser URL using History API without full page reload
     *
     * Manipulates the browser's URL bar and history using pushState or replaceState.
     * Accepts URL strings, query parameter arrays, or null to update in place.
     * Enforces single-use constraint to prevent multiple URL manipulations per response.
     *
     * @param mixed $url URL string, query parameter array, or null for current URL
     * @param string $mode History manipulation mode: 'push' or 'replace'
     *
     * @throws \InvalidArgumentException When mode is not 'push' or 'replace'
     *
     * @return static Returns this instance for method chaining
     */
    public function url(mixed $url = null, string $mode = 'push'): self
    {

        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        $urlManager = app(\Dancycodes\Hyper\Services\HyperUrlManager::class);
        $urlManager->enforceUrlSingleUse();

        if (!in_array($mode, ['push', 'replace'])) {
            throw new \InvalidArgumentException("URL mode must be 'push' or 'replace', got '{$mode}'");
        }

        $resolvedUrl = $urlManager->buildUrl($url);
        $urlManager->validateUrl($resolvedUrl);

        $script = $urlManager->generateHistoryScript($resolvedUrl, $mode);

        return $this->executeScript($script, ['autoRemove' => true]);
    }

    /**
     * Push new entry to browser history using History API
     *
     * Convenience method that calls url() with 'push' mode. Adds a new entry
     * to the browser's history stack, allowing back button navigation.
     *
     * @param mixed $url URL string, query parameter array, or null for current URL
     *
     * @return static Returns this instance for method chaining
     */
    public function pushUrl(mixed $url = null): self
    {
        return $this->url($url, 'push');
    }

    /**
     * Replace current browser history entry using History API
     *
     * Convenience method that calls url() with 'replace' mode. Modifies the current
     * history entry without creating a new one, preventing additional back button stops.
     *
     * @param mixed $url URL string, query parameter array, or null for current URL
     *
     * @return static Returns this instance for method chaining
     */
    public function replaceUrl(mixed $url = null): self
    {
        return $this->url($url, 'replace');
    }

    /**
     * Update browser URL using Laravel named route with History API
     *
     * Generates URL from Laravel route name and parameters, then updates browser
     * history. Includes route existence validation via URL manager service.
     *
     * @param string $routeName Laravel route name
     * @param array<string, mixed> $params Route parameters
     * @param string $mode History manipulation mode: 'push' or 'replace'
     *
     * @throws \InvalidArgumentException When mode is not 'push' or 'replace'
     *
     * @return static Returns this instance for method chaining
     */
    public function routeUrl(string $routeName, array $params = [], string $mode = 'push'): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        $urlManager = app(\Dancycodes\Hyper\Services\HyperUrlManager::class);
        $urlManager->enforceUrlSingleUse();

        if (!in_array($mode, ['push', 'replace'])) {
            throw new \InvalidArgumentException("URL mode must be 'push' or 'replace', got '{$mode}'");
        }

        $url = $urlManager->buildRouteUrl($routeName, $params);
        $script = $urlManager->generateHistoryScript($url, $mode);

        return $this->executeScript($script, ['autoRemove' => true]);
    }

    /**
     * Push Laravel named route to browser history
     *
     * Convenience method that generates URL from route name and pushes it to history.
     * Functionally equivalent to routeUrl() with 'push' mode.
     *
     * @param string $routeName Laravel route name
     * @param array<string, mixed> $params Route parameters
     *
     * @return static Returns this instance for method chaining
     */
    public function pushRoute(string $routeName, array $params = []): self
    {
        return $this->routeUrl($routeName, $params, 'push');
    }

    /**
     * Replace current history entry with Laravel named route
     *
     * Convenience method that generates URL from route name and replaces current history entry.
     * Functionally equivalent to routeUrl() with 'replace' mode.
     *
     * @param string $routeName Laravel route name
     * @param array<string, mixed> $params Route parameters
     *
     * @return static Returns this instance for method chaining
     */
    public function replaceRoute(string $routeName, array $params = []): self
    {
        return $this->routeUrl($routeName, $params, 'replace');
    }

    /**
     * Enable streaming mode for long-running operations
     *
     * Switches the response builder from accumulation mode to streaming mode, where events
     * are sent immediately as methods are called. The provided callback receives this instance
     * and executes in streaming context. Any events accumulated before stream() was called are
     * flushed first. Handles output buffering, exception rendering, dump/dd integration, and
     * redirect behavior for streaming responses.
     *
     * @param \Closure $callback Function receiving this instance in streaming mode
     *
     * @return static Returns this instance for method chaining
     */
    public function stream(Closure $callback): self
    {
        $this->streamCallback = function ($hyper) use ($callback) {
            try {
                ob_start();

                $this->overrideRedirectForStream();
                $this->overrideDumpForStream();

                $callback($hyper);

            } catch (\Throwable $e) {
                $this->handleNativeException($e);
            } finally {
                $this->handleStreamOutput();
                $this->restoreOriginalHandlers();
            }
        };

        return $this;
    }

    /**
     * Override Laravel redirect helper for streaming mode
     *
     * Binds custom redirect implementation that performs JavaScript-based navigation
     * via window.location and terminates the stream. Required because standard Laravel
     * redirects return Response objects incompatible with active SSE streams.
     */
    protected function overrideRedirectForStream(): void
    {
        app()->bind('redirect', function () {
            return new class
            {
                /**
                 * @param array<string, mixed> $headers
                 */
                public function to(string $path, int $status = 302, array $headers = [], ?bool $secure = null): never
                {
                    $this->performRealRedirect(url($path));
                }

                /**
                 * @param array<string, mixed> $params
                 * @param array<string, mixed> $headers
                 */
                public function route(string $route, array $params = [], int $status = 302, array $headers = []): never
                {
                    $this->performRealRedirect(route($route, $params));
                }

                /**
                 * @param array<string, mixed> $headers
                 */
                public function back(int $status = 302, array $headers = [], string|bool $fallback = false): never
                {
                    $this->performRealRedirect(url()->previous() ?: url('/'));
                }

                private function performRealRedirect(string $url): never
                {
                    echo "event: datastar-patch-elements\n";
                    echo 'data: elements <script>window.location.href = ' . json_encode($url) . ";</script>\n";
                    echo "data: selector body\n";
                    echo "data: mode append\n\n";

                    if (ob_get_level()) {
                        ob_end_flush();
                    }
                    flush();
                    exit;
                }

                /**
                 * @param array<int, mixed> $args
                 */
                public function __call(string $method, array $args): mixed
                {
                    $result = app('redirect')->{$method}(...$args);
                    if ($result instanceof \Illuminate\Http\RedirectResponse) {
                        $this->performRealRedirect($result->getTargetUrl());
                    }

                    return $result;
                }
            };
        });
    }

    /**
     * Override Symfony VarDumper for streaming mode
     *
     * Replaces the default VarDumper handler to intercept dd() and dump() calls,
     * rendering them as full HTML pages that replace the document and terminate
     * the stream. Prevents dump output from corrupting the SSE event stream.
     */
    protected function overrideDumpForStream(): void
    {
        \Symfony\Component\VarDumper\VarDumper::setHandler(function ($var) {
            $html = $this->generateNativeDumpHtml($var);
            $this->replaceDocumentAndExit($html);
        });
    }

    /**
     * Generate HTML page for variable dump output
     *
     * Creates a styled HTML document containing the variable dump using Symfony's
     * HtmlDumper. Replicates Laravel's dd() styling with dark theme and monospace fonts.
     *
     * @param mixed $var Variable to dump
     *
     * @return string Complete HTML document with embedded dump output
     */
    protected function generateNativeDumpHtml(mixed $var): string
    {
        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner;
        $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper;

        $output = '';
        $dumper->dump($cloner->cloneVar($var), function ($line) use (&$output) {
            $output .= $line;
        });

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laravel dd() Output</title>
    <style>
        body { background: #18171B; color: #FF8400; font-family: monospace; margin: 0; padding: 20px; }
        pre.sf-dump { background: #18171B !important; }
        .sf-dump { font-family: monospace; font-size: 12px; line-height: 1.2em; color: #FF8400; word-wrap: break-word; white-space: pre-wrap; position: relative; z-index: 99999; word-break: break-all; }
        .sf-dump .sf-dump-compact { display: none; }
        .sf-dump abbr { text-decoration: none; cursor: help; }
        .sf-dump a { text-decoration: none; cursor: pointer; outline: none; color: inherit; }
        .sf-dump .sf-dump-ellipsis { color: #A0A0A0; }
        .sf-dump .sf-dump-key { color: #A626A4; }
        .sf-dump .sf-dump-public { color: #222222; }
        .sf-dump .sf-dump-protected { color: #C41A16; }
        .sf-dump .sf-dump-private { color: #C41A16; }
        .sf-dump .sf-dump-str { color: #C41A16; }
        .sf-dump .sf-dump-note { color: #1299DA; }
        .sf-dump .sf-dump-ref { color: #6E7681; }
        .sf-dump .sf-dump-meta { color: #B729D9; }
    </style>
</head>
<body>' . $output . '</body>
</html>';
    }

    /**
     * Process output buffer and replace document if content exists
     *
     * Captures buffered output from the streaming callback and wraps it in a complete
     * HTML document if non-empty. Used to display echo statements and other direct
     * output during streaming mode. Terminates stream after document replacement.
     */
    protected function handleStreamOutput(): void
    {
        $output = ob_get_contents();
        if (ob_get_level()) {
            ob_end_clean();
        }

        /** @phpstan-ignore argument.type (ob_get_contents can return false on empty buffer) */
        if (!empty(trim($output))) {
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laravel Output</title>
</head>
<body style="background: #18171B; color: white; font-family: monospace; padding: 20px;">
    ' . $output . '
</body>
</html>';

            $this->replaceDocumentAndExit($html);
        }
    }

    /**
     * Replace entire browser document and terminate stream
     *
     * Sends JavaScript that replaces the complete document content using document.write()
     * and terminates the SSE stream. Used for showing full-page content during streaming
     * such as dump output, exceptions, or direct output captures.
     *
     * @param string $html Complete HTML document to display
     */
    protected function replaceDocumentAndExit(string $html): void
    {
        echo "event: datastar-patch-elements\n";
        echo 'data: elements <script>document.open(); document.write(' . json_encode($html) . "); document.close();</script>\n";
        echo "data: selector body\n";
        echo "data: mode append\n\n";

        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        exit;
    }

    /**
     * Restore original Laravel service bindings
     *
     * Removes custom redirect and VarDumper bindings established for streaming mode,
     * restoring default Laravel behavior. Called in finally block to ensure cleanup
     * occurs even when exceptions are thrown.
     */
    protected function restoreOriginalHandlers(): void
    {
        app()->forgetInstance('redirect');
        \Symfony\Component\VarDumper\VarDumper::setHandler(null);
    }

    /**
     * Render exception using Laravel exception handler
     *
     * Delegates exception rendering to Laravel's exception handler to generate appropriate
     * error pages (Ignition in development, generic in production). Falls back to basic
     * error HTML if exception handler itself throws an exception.
     *
     * @param \Throwable $e Exception to render
     */
    protected function handleNativeException(\Throwable $e): void
    {
        try {
            $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
            $response = $handler->render(request(), $e);
            $html = $response->getContent();

            /** @phpstan-ignore argument.type (getContent can return false, guarded by try-catch) */
            $this->replaceDocumentAndExit($html);

        } catch (\Throwable $renderError) {
            $fallbackHtml = $this->generateFallbackErrorHtml($e);
            $this->replaceDocumentAndExit($fallbackHtml);
        }
    }

    /**
     * Generate basic error HTML page when exception handler fails
     *
     * Creates minimal error page displaying exception message, file location, and stack
     * trace. Used as fallback when Laravel's exception handler itself throws an exception.
     *
     * @param \Throwable $e Original exception
     *
     * @return string HTML error page
     */
    protected function generateFallbackErrorHtml(\Throwable $e): string
    {
        return '<!DOCTYPE html>
<html>
<head><title>Error</title></head>
<body style="background: #ef4444; color: white; padding: 20px; font-family: monospace;">
    <h1>Exception in Stream</h1>
    <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
    <p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>
    <pre style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 5px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
</body>
</html>';
    }

    /**
     * Convert to HTTP response implementing Responsable interface
     *
     * Transforms this builder into a framework-compatible response object. For non-Hyper
     * requests, returns the web fallback if configured, otherwise throws LogicException.
     * For Hyper requests, creates a StreamedResponse with SSE headers that outputs either
     * accumulated events (normal mode) or executes streaming callback (streaming mode).
     *
     * @param \Illuminate\Http\Request|null $request Laravel request instance or null for auto-detection
     *
     * @throws \LogicException When no web fallback provided for non-Hyper request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|mixed StreamedResponse for Hyper, fallback for web
     */
    public function toResponse($request = null): mixed
    {
        $request = $request ?? request();

        // Handle non-Hyper requests
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!$request->isHyper()) {
            if ($this->webResponse === null) {
                throw new LogicException(
                    'No web response provided for non-Hyper request. Use ->web() to specify fallback response.'
                );
            }

            return is_callable($this->webResponse)
                ? ($this->webResponse)()
                : $this->webResponse;
        }

        // Handle Hyper requests
        $response = new StreamedResponse(function () {

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            if ($this->streamCallback) {
                $this->executeStreamingMode();
            } else {
                $this->executeSingleShotMode();
            }
        });

        foreach (self::headers() as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    /**
     * Handle event routing based on current response mode
     *
     * Central event dispatcher that routes events to appropriate handler based on whether
     * the response is in streaming or normal mode. Short-circuits immediately for non-Hyper
     * requests to avoid unnecessary processing.
     *
     * @param string $eventType SSE event type (datastar-patch-elements, datastar-patch-signals)
     * @param array<int, string> $dataLines SSE data lines for the event
     */
    protected function handleEvent(string $eventType, array $dataLines): void
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return;
        }

        if ($this->streamingMode) {
            $this->sendEventImmediately($eventType, $dataLines);
        } else {
            $this->addEventToQueue($eventType, $dataLines);
        }
    }

    /**
     * Execute response in streaming mode
     *
     * Flushes any events accumulated before streaming began, switches to streaming mode,
     * then executes the callback. All method calls during callback execution send events
     * immediately rather than accumulating them.
     */
    protected function executeStreamingMode(): void
    {
        foreach ($this->events as $event) {
            echo $event;
            $this->flushOutput();
        }

        $this->streamingMode = true;
        /** @phpstan-ignore argument.type (streamCallback is set in stream() method) */
        call_user_func($this->streamCallback, $this);
    }

    /**
     * Execute response in normal single-shot mode
     *
     * Outputs all accumulated events sequentially and closes the connection. Used when
     * stream() method was not called, representing standard request-response pattern.
     */
    protected function executeSingleShotMode(): void
    {
        foreach ($this->events as $event) {
            echo $event;
            $this->flushOutput();
        }
    }

    /**
     * Send SSE event immediately to client
     *
     * Formats and outputs event directly, then flushes buffers to ensure immediate
     * transmission. Used exclusively in streaming mode.
     *
     * @param string $eventType SSE event type
     * @param array<int, string> $dataLines SSE data lines
     */
    protected function sendEventImmediately(string $eventType, array $dataLines): void
    {
        $output = $this->formatEvent($eventType, $dataLines);
        echo $output;
        $this->flushOutput();
    }

    /**
     * Add formatted SSE event to accumulation queue
     *
     * Stores event for later transmission when response is converted. Used exclusively
     * in normal mode before stream() is called.
     *
     * @param string $eventType SSE event type
     * @param array<int, string> $dataLines SSE data lines
     */
    protected function addEventToQueue(string $eventType, array $dataLines): void
    {
        $this->events[] = $this->formatEvent($eventType, $dataLines);
    }

    /**
     * Flush PHP output buffers to client
     *
     * Forces immediate transmission of buffered output to client. Used in streaming
     * mode to ensure events are received as soon as they're generated.
     */
    protected function flushOutput(): void
    {
        if (ob_get_contents()) {
            ob_end_flush();
        }
        flush();
    }

    /**
     * Format SSE event according to Datastar protocol
     *
     * Constructs properly formatted Server-Sent Event with event type and data lines,
     * terminated by blank line as required by SSE specification.
     *
     * @param string $eventType Event type line (event: xxx)
     * @param array<int, string> $dataLines Data payload lines (data: xxx)
     *
     * @return string Formatted SSE event block
     */
    protected function formatEvent(string $eventType, array $dataLines): string
    {
        $output = ["event: {$eventType}"];

        foreach ($dataLines as $line) {
            /** @phpstan-ignore function.alreadyNarrowedType (dataLines array items are already strings per PHPDoc) */
            $lineStr = is_string($line) ? $line : (string) $line;
            $output[] = "data: {$lineStr}";
        }

        $output[] = '';

        return implode("\n", $output) . "\n";
    }

    /**
     * Build SSE data lines for element patching event
     *
     * Constructs array of SSE data lines for datastar-patch-elements event including
     * selector, mode, view transition flag, and multi-line HTML element content.
     *
     * @param string $elements HTML content to patch
     * @param array<string, mixed> $options Patching options (selector, mode, useViewTransition)
     *
     * @return array<int, string> Array of SSE data lines
     */
    protected function buildElementsEvent(string $elements, array $options): array
    {
        $dataLines = [];

        if (!empty($options['selector'])) {
            /** @phpstan-ignore cast.string (mixed array value, safe to cast) */
            $dataLines[] = 'selector ' . (string) $options['selector'];
        }

        if (!empty($options['mode'])) {
            /** @phpstan-ignore cast.string (mixed array value, safe to cast) */
            $dataLines[] = 'mode ' . (string) $options['mode'];
        }

        if (!empty($options['useViewTransition'])) {
            $dataLines[] = 'useViewTransition true';
        }

        $elementLines = explode("\n", trim($elements));
        foreach ($elementLines as $line) {
            $dataLines[] = "elements {$line}";
        }

        return $dataLines;
    }

    /**
     * Build SSE data lines for signal patching event
     *
     * Constructs array of SSE data lines for datastar-patch-signals event including
     * onlyIfMissing flag and JSON-encoded signal data split across multiple lines.
     *
     * @param array<string, mixed> $signals Signal names and values to patch
     * @param array<string, mixed> $options Signal options (onlyIfMissing)
     *
     * @return array<int, string> Array of SSE data lines
     */
    protected function buildSignalsEvent(array $signals, array $options): array
    {
        $dataLines = [];

        if (!empty($options['onlyIfMissing'])) {
            $dataLines[] = 'onlyIfMissing true';
        }

        $signalsJson = json_encode($signals);
        /** @phpstan-ignore argument.type (json_encode result is always string for arrays) */
        $jsonLines = explode("\n", $signalsJson);

        foreach ($jsonLines as $line) {
            $dataLines[] = "signals {$line}";
        }

        return $dataLines;
    }

    /**
     * Build SSE data lines for script execution event
     *
     * Constructs array of SSE data lines for executing JavaScript by creating a script
     * element appended to document body. Supports custom attributes and auto-removal
     * after execution via Datastar's data-effect directive.
     *
     * @param string $script JavaScript code to execute
     * @param array<string, mixed> $options Script configuration (attributes, autoRemove)
     *
     * @return array<int, string> Array of SSE data lines
     */
    protected function buildScriptEvent(string $script, array $options): array
    {
        /** @var array<string, string> $attributes */
        $attributes = $options['attributes'] ?? [];
        $autoRemove = $options['autoRemove'] ?? true;

        $scriptTag = '<script';

        foreach ($attributes as $key => $value) {
            $scriptTag .= ' ' . (string) $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
        }

        if ($autoRemove) {
            $scriptTag .= ' data-effect="el.remove()"';
        }

        $scriptTag .= '>' . $script . '</script>';

        // Split script tag by newlines to properly format SSE data lines
        $dataLines = ['selector body', 'mode append'];
        $scriptLines = explode("\n", trim($scriptTag));
        foreach ($scriptLines as $line) {
            $dataLines[] = "elements {$line}";
        }

        return $dataLines;
    }

    /**
     * Build SSE data lines for element removal event
     *
     * Constructs array of SSE data lines for removing DOM elements using patch mode 'remove'.
     * Supports View Transitions API for smooth removal animations.
     *
     * @param array<string, mixed> $options Removal configuration (selector, useViewTransition)
     *
     * @return array<int, string> Array of SSE data lines
     */
    protected function buildRemovalEvent(array $options): array
    {
        $dataLines = [
            /** @phpstan-ignore cast.string (mixed array value, safe to cast) */
            'selector ' . (string) $options['selector'],
            'mode remove',
        ];

        if (!empty($options['useViewTransition'])) {
            $dataLines[] = 'useViewTransition true';
        }

        return $dataLines;
    }

    /**
     * Render and patch a Blade fragment without full view compilation
     *
     * Extracts the specified fragment from the view using BladeFragmentParser and renders
     * it with provided data, then patches the result into the DOM. Only processes for Hyper
     * requests to avoid unnecessary fragment extraction for standard requests.
     *
     * @param string $view Blade view containing the fragment
     * @param string $fragment Fragment name to extract and render
     * @param array<string, mixed> $data Variables to pass to fragment
     * @param array<string, mixed> $options DOM patching options (selector, mode, useViewTransition)
     *
     * @return static Returns this instance for method chaining
     */
    protected function patchFragment(string $view, string $fragment, array $data = [], array $options = []): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        $fragmentHtml = BladeFragment::render($view, $fragment, $data);

        return $this->patchElements($fragmentHtml, $options);
    }

    /**
     * Render and patch multiple Blade fragments in sequence
     *
     * Processes array of fragment configurations, rendering and patching each fragment.
     * Enables updating multiple UI sections in a single response without full page reloads.
     *
     * @param array<int, array{view: string, fragment: string, data?: array<string, mixed>, options?: array<string, mixed>}> $fragments Fragment configurations
     *
     * @return static Returns this instance for method chaining
     */
    protected function patchFragments(array $fragments): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        foreach ($fragments as $fragmentConfig) {
            $view = $fragmentConfig['view'];
            $fragment = $fragmentConfig['fragment'];
            $data = $fragmentConfig['data'] ?? [];
            $options = $fragmentConfig['options'] ?? [];

            $this->patchFragment($view, $fragment, $data, $options);
        }

        return $this;
    }

    /**
     * Render fragment and return HTML string without patching
     *
     * Extracts and renders fragment but returns the HTML as a string instead of patching
     * it into the DOM. Useful for embedding fragment content in other contexts or for
     * manual DOM manipulation.
     *
     * @param string $view Blade view containing the fragment
     * @param string $fragment Fragment name to extract and render
     * @param array<string, mixed> $data Variables to pass to fragment
     *
     * @return string Rendered HTML content of the fragment
     */
    protected function renderFragment(string $view, string $fragment, array $data = []): string
    {
        return BladeFragment::render($view, $fragment, $data);
    }

    /**
     * Configure fallback response for non-Hyper requests
     *
     * Sets the response to return when the request does not include the Datastar-Request
     * header. Accepts any value that Laravel can convert to a response, including Response
     * objects, views, redirects, or closures that return these types. If not configured,
     * LogicException is thrown for non-Hyper requests when toResponse() is called.
     *
     * @param mixed $response Response value or closure returning response
     *
     * @return static Returns this instance for method chaining
     */
    public function web(mixed $response): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (request()->isHyper()) {
            return $this;
        }

        $this->webResponse = $response;

        return $this;
    }

    /**
     * Execute callback conditionally based on evaluated condition
     *
     * Provides Laravel-style conditional method chaining where callbacks receive this
     * instance and can add events conditionally. Condition can be boolean value or
     * callable that receives this instance and returns boolean. Supports optional
     * fallback callback for else branch.
     *
     * @param mixed $condition Boolean value or callable returning boolean
     * @param callable $callback Callback to execute if condition is truthy
     * @param callable|null $fallback Optional callback to execute if condition is falsy
     *
     * @return static Returns this instance for method chaining
     */
    public function when($condition, callable $callback, ?callable $fallback = null): self
    {
        $conditionResult = is_callable($condition) ? $condition($this) : $condition;

        if ($conditionResult) {
            $callback($this);
        } elseif ($fallback) {
            $fallback($this);
        }

        return $this;
    }

    /**
     * Execute callback when condition evaluates to false
     *
     * Inverted conditional that executes callback when condition is falsy. Equivalent
     * to when() with negated condition. Useful for clearer conditional logic in certain
     * scenarios.
     *
     * @param mixed $condition Boolean value or callable returning boolean
     * @param callable $callback Callback to execute if condition is falsy
     * @param callable|null $fallback Optional callback to execute if condition is truthy
     *
     * @return static Returns this instance for method chaining
     */
    public function unless($condition, callable $callback, ?callable $fallback = null): self
    {
        return $this->when(!$condition, $callback, $fallback);
    }

    /**
     * Execute callback only for Hyper requests
     *
     * Convenience method for conditional execution based on request type. Automatically
     * detects Hyper requests via Datastar-Request header and executes callback only
     * when present. Supports optional fallback for non-Hyper requests.
     *
     * @param callable $callback Callback to execute for Hyper requests
     * @param callable|null $fallback Optional callback for non-Hyper requests
     *
     * @return static Returns this instance for method chaining
     */
    public function whenHyper(callable $callback, ?callable $fallback = null): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        return $this->when(request()->isHyper(), $callback, $fallback);
    }

    /**
     * Execute callback only for non-Hyper requests
     *
     * Inverse of whenHyper() that executes callback for standard HTTP requests without
     * Datastar-Request header. Useful for providing alternate behavior for traditional
     * full-page requests.
     *
     * @param callable $callback Callback to execute for non-Hyper requests
     * @param callable|null $fallback Optional callback for Hyper requests
     *
     * @return static Returns this instance for method chaining
     */
    public function whenNotHyper(callable $callback, ?callable $fallback = null): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        return $this->when(!request()->isHyper(), $callback, $fallback);
    }

    /**
     * Delete specified signals from client-side signal store
     *
     * Converts signal names to deletion array with null values per Datastar protocol,
     * then sends update event to remove signals from client. Supports single signal
     * name or array of signal names.
     *
     * @param string|array<int, string> $signals Signal name or array of signal names to delete
     *
     * @return static Returns this instance for method chaining
     */
    protected function forgetSignals(string|array $signals): self
    {
        $deletionArray = $this->parseSignalsForDeletion($signals);

        return $this->updateSignals($deletionArray);
    }

    /**
     * Create deletion array with null values for signal removal
     *
     * Transforms signal names into associative array format required for deletion,
     * where each signal name maps to null value to trigger client-side removal per
     * Datastar protocol. The 'errors' signal receives special treatment and is reset
     * to an empty array instead of null, as it should always remain an array for the
     * data-error attribute to function correctly.
     *
     * @param string|array<int, string> $signals Signal name or array of signal names
     *
     * @return array<string, null|array<empty, empty>> Deletion array with signal names as keys, null or empty array as values
     */
    private function parseSignalsForDeletion(string|array $signals): array
    {
        $signalNames = $this->parseSignalNames($signals);

        $deletionArray = [];
        foreach ($signalNames as $signalName) {
            // Special handling for errors signal - reset to empty array instead of null
            // The errors signal is used by data-error attribute and should always be an array
            $deletionArray[$signalName] = ($signalName === 'errors') ? [] : null;
        }

        return $deletionArray;
    }

    /**
     * Extract signal names from various input formats
     *
     * Normalizes different input formats (single string, indexed array, associative array)
     * into flat array of signal name strings. For associative arrays, extracts keys as
     * signal names. For indexed arrays, uses values as signal names.
     *
     * @param string|array<int|string, mixed> $signals Signal name(s) in various formats
     *
     * @return array<int, string> Normalized array of signal name strings
     */
    private function parseSignalNames(string|array $signals): array
    {
        if (is_string($signals)) {
            return [$signals];
        }

        if (array_keys($signals) !== range(0, count($signals) - 1)) {
            return array_map(fn ($key) => (string) $key, array_keys($signals));
        }

        /** @phpstan-ignore cast.string (Signal values are converted to strings for consistency) */
        return array_map(fn ($value) => (string) $value, array_values($signals));
    }

    /**
     * Execute callback for Hyper navigate requests matching optional key
     *
     * Conditional execution specific to navigate requests identified by Hyper-Navigate
     * header. Supports filtering by navigate key for targeted updates (e.g., sidebar vs
     * main content). When first parameter is callable, treats request as "any navigate".
     *
     * @param string|array<int, string>|callable|null $key Navigate key filter, array of keys, or callback for any navigate
     * @param callable|null $callback Callback to execute when navigate request matches
     * @param callable|null $fallback Optional callback for non-matching requests
     *
     * @return static Returns this instance for method chaining
     */
    public function whenHyperNavigate(string|array|callable|null $key = null, ?callable $callback = null, ?callable $fallback = null): self
    {
        if (is_callable($key)) {
            $fallback = $callback;
            $callback = $key;
            $key = null;
        }

        /** @phpstan-ignore method.notFound (isHyperNavigate is a Request macro) */
        $isNavigateRequest = request()->isHyperNavigate($key);

        if ($isNavigateRequest && $callback) {
            $callback($this);
        } elseif (!$isNavigateRequest && $fallback) {
            $fallback($this);
        }

        return $this;
    }

    /**
     * Force immediate full-page reload breaking out of reactive mode
     *
     * Calls the @reload() Datastar action to perform a complete page refresh,
     * discarding all client-side state and re-initializing the application. Used for
     * scenarios requiring complete state reset or when reactive updates are insufficient.
     *
     * @return static Returns this instance for method chaining
     */
    public function reload(): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        // Call @reload() Datastar action (proper integration)
        $script = '@reload()';

        return $this->executeScript($script, ['autoRemove' => true]);
    }

    /**
     * Generate navigation script using Datastar action
     *
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore method.unused (Reserved for future frontend integration)
     */
    private function generateNavigateScript(string $url, ?string $key, array $options = []): string
    {
        $safeUrl = json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeKey = json_encode($key ?: 'true', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeOptions = json_encode($this->normalizeNavigationOptions($options, $url), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Call @navigate Datastar action (proper integration)
        return "@navigate({$safeUrl}, {$safeKey}, {$safeOptions})";
    }

    /**
     * Generate back navigation script using Datastar action
     *
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore method.unused (Reserved for future frontend integration)
     */
    private function generateBackScript(string $fallbackUrl, string $key, array $options = []): string
    {
        $safeFallbackUrl = json_encode($fallbackUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeKey = json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $safeOptions = json_encode($this->normalizeNavigationOptions($options, $fallbackUrl), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Call @back Datastar action (proper integration)
        return "@back({$safeFallbackUrl}, {$safeKey}, {$safeOptions})";
    }

    /**
     * Generate refresh script using Datastar action
     *
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore method.unused (Reserved for future frontend integration)
     */
    private function generateRefreshScript(string $key, array $options = []): string
    {
        $safeKey = json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        // For refresh, we always want to merge by default since we're refreshing current state
        $refreshOptions = ['merge' => $options['merge'] ?? true] + $options;
        $safeOptions = json_encode($this->normalizeNavigationOptions($refreshOptions, ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Call @refresh Datastar action (proper integration)
        return "@refresh({$safeKey}, {$safeOptions})";
    }

    /**
     * Normalize navigation options with SMART defaults based on URL
     *
     * @param array<string, mixed> $options Raw options from user
     * @param string $url The target URL to analyze
     *
     * @return array<string, mixed> Normalized options with smart defaults
     */
    private function normalizeNavigationOptions(array $options = [], string $url = ''): array
    {
        $merge = $options['merge'] ?? null;

        // If merge not explicitly set, use smart default based on URL
        if ($merge === null) {
            $merge = $this->shouldMergeByDefault($url);
        }

        return [
            'merge' => $merge,
            'only' => $options['only'] ?? null,
            'except' => $options['except'] ?? null,
        ];
    }

    /**
     * Determine if URL should merge by default (matches frontend logic)
     *
     * Logic:
     * a) Simple URLs without query params: /dashboard → DON'T merge
     * b) URLs with query params: /dashboard?search=john → DO merge
     * c) Query-only URLs: ?search=john → DO merge
     */
    private function shouldMergeByDefault(string $url): bool
    {
        // Query-only URLs (start with ?) should always merge
        if (str_starts_with($url, '?')) {
            return true;
        }

        // Check if URL has query parameters
        return str_contains($url, '?');
    }

    /**
     * Smart navigation based on context (convenience method)
     *
     * Automatically chooses merge behavior based on common patterns
     *
     * @param string $url Target URL
     * @param string|null $key Navigation key
     * @param string $context Context hint (auth, admin, export, etc.)
     */
    public function smartNavigate(string $url, ?string $key = null, string $context = 'default'): self
    {
        $options = $this->getSmartNavigationOptions($url, $context);

        return $this->navigate($url, $key ?? 'true', $options);
    }

    /**
     * Get smart navigation options based on context
     *
     * @return array<string, mixed>
     */
    private function getSmartNavigationOptions(string $url, string $context): array
    {
        // Auth-related routes should clear parameters
        if ($context === 'auth' || preg_match('/\/(login|logout|register|reset)/', $url)) {
            return ['merge' => false];
        }

        // Export routes might want to preserve search/filter context
        if ($context === 'export' || str_contains($url, '/export')) {
            return ['except' => ['page']]; // Remove pagination but keep filters
        }

        // Admin routes might want selective preservation
        if ($context === 'admin' || str_starts_with($url, '/admin/')) {
            return ['except' => ['user_context']];
        }

        // Error/reset scenarios should clear everything
        if ($context === 'error' || $context === 'reset') {
            return ['merge' => false];
        }

        // Default: preserve everything
        return ['merge' => true];
    }

    /**
     * Navigate to URL with explicit merge control and comprehensive options
     *
     * REPLACES THE EXISTING navigate() METHOD WITH EXPLICIT BEHAVIOR
     *
     * @param string|array<string, mixed> $url URL string or array of query parameters
     * @param string $key Navigation key for Datastar routing
     * @param array<string, mixed> $options Navigation options
     */
    public function navigate(string|array $url, string $key = 'true', array $options = []): self
    {
        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if (!request()->isHyper()) {
            return $this;
        }

        $urlManager = app(\Dancycodes\Hyper\Services\HyperUrlManager::class);
        $urlManager->enforceUrlSingleUse();

        // Process input based on type - NO backend merging, let frontend handle it
        if (is_array($url)) {
            // JSON query array navigation - convert to current path with queries
            $queryString = $this->buildQueryString($url);
            $currentPath = request()->getPathInfo();
            $finalUrl = $queryString ? "{$currentPath}?{$queryString}" : $currentPath;
        } else {
            // Traditional string URL navigation - send as-is
            $finalUrl = $url;
        }

        $urlManager->validateUrl($finalUrl);

        // Generate navigation script with comprehensive options
        // Frontend @navigate action will handle all merging based on window.location
        $script = $this->generateEnhancedNavigateScript($finalUrl, $key, $options);

        return $this->executeScript($script, ['autoRemove' => true]);
    }

    /**
     * Navigate with explicit merge behavior (RECOMMENDED)
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param string $key Navigation key
     * @param bool $merge Whether to merge with current query parameters
     * @param array<string, mixed> $options Additional options (only, except, replace)
     */
    public function navigateWith(string|array $url, string $key = 'true', bool $merge = false, array $options = []): self
    {
        return $this->navigate($url, $key, array_merge($options, ['merge' => $merge]));
    }

    /**
     * Navigate and merge with current parameters (preserves context)
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param string $key Navigation key
     * @param array<string, mixed> $options Additional options
     */
    public function navigateMerge(string|array $url, string $key = 'true', array $options = []): self
    {
        return $this->navigateWith($url, $key, true, $options);
    }

    /**
     * Navigate with clean slate (no parameter merging)
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param string $key Navigation key
     * @param array<string, mixed> $options Additional options
     */
    public function navigateClean(string|array $url, string $key = 'true', array $options = []): self
    {
        return $this->navigateWith($url, $key, false, $options);
    }

    /**
     * Navigate preserving only specific parameters
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param array<int, string> $only Parameters to preserve
     * @param string $key Navigation key
     */
    public function navigateOnly(string|array $url, array $only, string $key = 'true'): self
    {
        return $this->navigate($url, $key, ['merge' => true, 'only' => $only]);
    }

    /**
     * Navigate preserving all except specific parameters
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param array<int, string> $except Parameters to exclude
     * @param string $key Navigation key
     */
    public function navigateExcept(string|array $url, array $except, string $key = 'true'): self
    {
        return $this->navigate($url, $key, ['merge' => true, 'except' => $except]);
    }

    /**
     * Navigate using replaceState instead of pushState
     *
     * @param string|array<string, mixed> $url URL or query array
     * @param string $key Navigation key
     * @param array<string, mixed> $options Additional options
     */
    public function navigateReplace(string|array $url, string $key = 'true', array $options = []): self
    {
        return $this->navigate($url, $key, array_merge($options, ['replace' => true]));
    }

    /**
     * CONVENIENCE METHODS FOR COMMON PATTERNS
     */

    /**
     * Navigate to current page with new query parameters (maintains path)
     *
     * @param array<string, mixed> $queries Query parameters to set
     * @param string $key Navigation key
     * @param bool $merge Whether to merge with existing parameters
     */
    public function updateQueries(array $queries, string $key = 'filters', bool $merge = true): self
    {
        return $this->navigate($queries, $key, ['merge' => $merge]);
    }

    /**
     * Clear specific query parameters
     *
     * @param array<int, string> $paramNames Parameters to clear
     * @param string $key Navigation key
     */
    public function clearQueries(array $paramNames, string $key = 'clear'): self
    {
        $clearQueries = array_fill_keys($paramNames, null);

        return $this->navigate($clearQueries, $key, ['merge' => true]);
    }

    /**
     * Reset to page 1 while preserving other filters
     *
     * @param string $key Navigation key
     */
    public function resetPagination(string $key = 'pagination'): self
    {
        return $this->navigate(['page' => 1], $key, ['merge' => true]);
    }

    /**
     * INTERNAL PROCESSING METHODS
     */

    /**
     * Build query string from associative array
     *
     * @param array<string, mixed> $queries Query parameters
     *
     * @return string Query string (without ?)
     */
    private function buildQueryString(array $queries): string
    {
        $params = [];

        foreach ($queries as $key => $value) {
            if ($value === null || $value === '') {
                // Skip null/empty values - they clear parameters
                continue;
            }

            if (is_array($value)) {
                // Handle arrays (multi-select, checkboxes, etc.)
                foreach ($value as $item) {
                    if ($item !== null && $item !== '') {
                        /** @phpstan-ignore cast.string (Safe cast for URL encoding) */
                        $params[] = urlencode((string) $key) . '[]=' . urlencode((string) $item);
                    }
                }
            } else {
                // Handle scalar values
                /** @phpstan-ignore cast.string (Safe cast for URL encoding) */
                $params[] = urlencode((string) $key) . '=' . urlencode((string) $value);
            }
        }

        return implode('&', $params);
    }

    /**
     * Generate enhanced navigation script using Datastar action
     *
     * @param string $url Target URL
     * @param string $key Navigation key
     * @param array<string, mixed> $options Navigation options
     *
     * @return string JavaScript code
     */
    private function generateEnhancedNavigateScript(string $url, string $key, array $options): string
    {
        // Build options for frontend - pass ALL navigation options
        $frontendOptions = [];

        if (isset($options['merge'])) {
            $frontendOptions['merge'] = (bool) $options['merge'];
        }

        if (!empty($options['only'])) {
            $frontendOptions['only'] = $options['only'];
        }

        if (!empty($options['except'])) {
            $frontendOptions['except'] = $options['except'];
        }

        if (!empty($options['replace'])) {
            $frontendOptions['replace'] = true;
        }

        // DATASTAR-NATIVE APPROACH: Use signals to trigger navigation
        // Set a special __hyperNavigate signal that the frontend watcher will detect
        // This is the proper Datastar way - reactive signal-based triggering!
        $navigationData = [
            'url' => $url,
            'key' => $key,
            'options' => $frontendOptions,
            'timestamp' => microtime(true), // Ensure uniqueness to trigger reactivity
        ];

        $safeData = json_encode($navigationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        // Dispatch a custom DOM event that our navigate watcher will listen to
        // This works in script execution context and is Datastar-compatible
        return "document.dispatchEvent(new CustomEvent('hyper:navigate', { detail: {$safeData} }))";
    }

    // COMPATIBILITY METHODS - DEPRECATED BUT MAINTAINED

    /**
     * @deprecated Use navigateWith() or navigate() with explicit options
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $options
     */
    public function route(string $routeName, array $params = [], ?string $key = null, array $options = []): self
    {
        $url = route($routeName, $params);

        return $this->navigate($url, $key ?? 'route', $options);
    }

    /**
     * @deprecated Use navigateWith() or navigate() with explicit options
     *
     * @param array<string, mixed> $options
     */
    public function back(string $fallbackUrl = '/', string $key = 'back', array $options = []): self
    {
        // Try to get referrer, fallback to provided URL
        $backUrl = request()->headers->get('referer', $fallbackUrl);

        // Only use referrer if it's from same origin
        if ($backUrl && $backUrl !== request()->url()) {
            $referrerHost = parse_url($backUrl, PHP_URL_HOST);
            $currentHost = parse_url(request()->url(), PHP_URL_HOST);

            if ($referrerHost !== $currentHost) {
                $backUrl = $fallbackUrl;
            }
        }

        return $this->navigate($backUrl ?? $fallbackUrl, $key, array_merge(['merge' => true], $options));
    }

    /**
     * @deprecated Use updateQueries() or navigate() with query array
     *
     * @param array<string, mixed> $options
     */
    public function refresh(string $key = 'refresh', array $options = []): self
    {
        $currentUrl = request()->fullUrl();

        return $this->navigate($currentUrl, $key, array_merge(['merge' => true], $options));
    }
}
