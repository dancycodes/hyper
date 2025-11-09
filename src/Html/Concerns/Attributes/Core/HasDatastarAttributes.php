<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Core;

use Closure;

/**
 * Datastar reactive attributes for Laravel Hyper integration
 *
 * IMPORTANT: Most Datastar attributes accept static strings only because they
 * contain client-side reactive expressions (like $count, @postx('/save')).
 * These are evaluated by Datastar on the client, not the server.
 *
 * EXCEPTION: dataSignals() accepts closures for dynamic initial state.
 *
 * @see https://data-star.dev
 */
trait HasDatastarAttributes
{
    /**
     * Initialize Datastar signals with tamper protection for locked signals
     *
     * This is the ONLY Datastar method that accepts closures, allowing
     * dynamic initial state generation from the server.
     *
     * **Signal Types:**
     * - Regular signals: Standard reactive state (e.g., `count`, `email`)
     * - Local signals: Prefix with `_` (e.g., `_mode`) - Client-side only, not sent back
     * - Locked signals: Suffix with `_` (e.g., `userId_`) - Protected from tampering
     *
     * **Locked Signal Security:**
     * Signals ending with `_` are stored encrypted in session for tamper protection.
     * Use for sensitive data like user IDs, permissions, admin status, etc.
     *
     * Example:
     * ```php
     * // Mixed signal types
     * Html::div()->dataSignals([
     *     'count' => 0,              // Regular signal
     *     '_mode' => 'edit',         // Local signal (client-side only)
     *     'userId_' => auth()->id()  // Locked signal (tamper-protected)
     * ])
     *
     * // Dynamic signals with dependency injection
     * Html::div()->dataSignals(fn(UserService $users) => [
     *     'userId_' => auth()->id(),
     *     'userCount' => $users->count(),
     * ])
     * ```
     *
     * @param  array|Closure  $data  Signal data array or closure returning array
     */
    public function dataSignals(array|Closure $data): static
    {
        // 1. Evaluate if closure (allows dynamic initial state from server)
        $data = $this->evaluate($data);

        // 2. Convert Laravel types to arrays (models, collections, etc.)
        $data = $this->convertToArray($data);

        // 3. Store locked signals (ending with _) in encrypted session for tamper protection
        // This integrates with Laravel Hyper's signal validation system
        $this->storeLockedSignalsIfNeeded($data);

        // 4. JSON encode with security flags (XSS protection)
        // Same flags as Laravel Hyper's @signals directive
        $json = json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );

        // 5. Set as data-signals attribute (Datastar reads this to initialize reactive state)
        return $this->attr('data-signals', $json);
    }

    /**
     * Store locked signals in encrypted session for tamper protection
     *
     * Filters signal data for keys ending with underscore suffix (locked signals),
     * then stores them encrypted in session via HyperSignal. On subsequent requests,
     * Hyper validates client-sent locked signals against server-stored values to
     * detect tampering attempts.
     *
     * Integrates with the existing @signals Blade directive locked signal system.
     *
     * @param  array<string, mixed>  $signals  Signal data to check for locked signals
     */
    protected function storeLockedSignalsIfNeeded(array $signals): void
    {
        // Filter for locked signals (keys ending with _)
        $lockedSignals = array_filter($signals, function ($value, $key) {
            return str_ends_with((string) $key, '_');
        }, ARRAY_FILTER_USE_BOTH);

        // If locked signals exist, delegate to HyperSignal for encrypted storage
        if (! empty($lockedSignals)) {
            /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
            $hyperSignal = signals();
            $hyperSignal->storeLockedSignals($signals);
        }
    }

    /**
     * Convert Laravel types to arrays for JSON serialization
     *
     * Handles Eloquent models, Collections, JsonSerializable, Arrayable.
     * Priority order: JsonSerializable > toArray() > primitives
     */
    protected function convertToArray(mixed $value): mixed
    {
        if (is_array($value)) {
            // Recursively convert array values
            return array_map(fn ($item) => $this->convertToArray($item), $value);
        }

        // JsonSerializable (priority over toArray)
        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        // Eloquent Model / Arrayable (Laravel collections, etc.)
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        // Return as-is for primitives
        return $value;
    }

    /**
     * Two-way data binding
     *
     * Binds a form input value to a Datastar signal (two-way sync)
     *
     * @param  string  $signal  Signal name to bind to (e.g., 'email', 'title')
     */
    public function dataBind(string $signal): static
    {
        return $this->attr('data-bind', $signal);
    }

    /**
     * Display reactive text content
     *
     * Updates element text content reactively based on signal expression
     *
     * @param  string  $expression  Signal expression (e.g., '$count', '$user.name')
     */
    public function dataText(string $expression): static
    {
        return $this->attr('data-text', $expression);
    }

    /**
     * Event handling with Datastar modifiers
     *
     * Datastar uses double underscores (__) for modifiers and dots (.) for modifier options.
     * Modifiers can be passed as part of the event string OR as a separate array.
     *
     * Modifier syntax: data-on:EVENT__MODIFIER__MODIFIER.OPTION
     *
     * Available modifiers:
     * - prevent: Calls preventDefault()
     * - stop: Calls stopPropagation()
     * - once: Fire only once
     * - passive: Passive event listener
     * - capture: Use capture phase
     * - debounce.{time}ms: Debounce execution (options: leading, notrail)
     * - throttle.{time}ms: Throttle execution (options: noleading, trail)
     * - delay.{time}ms: Simple delay
     * - window: Attach to window object
     * - outside: Trigger when event outside element
     * - document: Attach to document
     * - viewtransition: Use View Transition API
     * - trusted: Run even if event.isTrusted is false
     * - case.{camel|kebab|snake|pascal}: Event name case conversion
     *
     * Examples:
     * ```php
     * // String format (modifiers in event name)
     * ->dataOn('click', '@postx("/save")')
     * ->dataOn('submit__prevent', '@postx("/save")')
     * ->dataOn('input__debounce.300ms', '@get("/search")')
     * ->dataOn('click__once__prevent', 'handleAction()')
     *
     * // Array format (modifiers as array)
     * ->dataOn('submit', '@postx("/save")', ['prevent'])
     * ->dataOn('input', '@get("/search")', ['debounce.300ms'])
     * ->dataOn('click', 'handleAction()', ['once', 'prevent'])
     * ->dataOn('resize', '$width = window.innerWidth', ['window', 'throttle.250ms'])
     * ```
     *
     * @param  string  $event  Event name (e.g., 'click', 'submit') or event with modifiers (e.g., 'submit__prevent')
     * @param  string  $action  Datastar action or expression
     * @param  array<string>  $modifiers  Optional array of modifiers (e.g., ['prevent'], ['debounce.300ms', 'leading'])
     *
     * @see https://data-star.dev/reference/plugins/backend
     */
    public function dataOn(string $event, string $action, array $modifiers = []): static
    {
        // If modifiers array provided, append them with double underscore
        if (! empty($modifiers)) {
            $event .= '__'.implode('__', $modifiers);
        }

        // No conversion needed - Datastar expects double underscores in the attribute name
        // Format: data-on:{event}__{modifier}__{modifier}.{option}
        return $this->attr('data-on:'.$event, $action);
    }

    /**
     * Conditional visibility (CSS display: none/block)
     *
     * Element stays in DOM, visibility controlled by CSS.
     *
     * @see https://data-star.dev/reference/plugins/visibility
     *
     * @param  string  $condition  JavaScript expression (e.g., '$isVisible', '$count > 0')
     */
    public function dataShow(string $condition): static
    {
        return $this->attr('data-show', $condition);
    }

    /**
     * Conditional rendering (removes from DOM)
     *
     * Element is completely removed from DOM when condition is false.
     *
     * @see https://data-star.dev/reference/plugins/visibility
     *
     * @param  string  $condition  JavaScript expression (e.g., '$showContent', '$user !== null')
     */
    public function dataIf(string $condition): static
    {
        return $this->attr('data-if', $condition);
    }

    /**
     * Iteration over arrays with key-based diffing
     *
     * Repeats element for each item in array.
     * Use $key parameter for efficient DOM diffing based on item property.
     *
     * @see https://data-star.dev/reference/plugins/visibility
     *
     * @param  string  $expression  Iteration expression (e.g., 'item in $items', 'user, index in $users')
     * @param  string|null  $key  Optional key property for diffing (e.g., 'id', 'uuid')
     */
    public function dataFor(string $expression, ?string $key = null): static
    {
        $attrName = 'data-for';

        // Only add key modifier if explicitly provided for efficient DOM diffing
        if ($key !== null) {
            $attrName .= "__key.{$key}";
        }

        return $this->attr($attrName, $expression);
    }

    /**
     * Display validation errors (Laravel Hyper extension)
     *
     * Automatically displays validation errors for a given field from Laravel
     *
     * @param  string  $field  Field name to display errors for (e.g., 'email', 'title')
     */
    public function dataError(string $field): static
    {
        return $this->attr('data-error', $field);
    }

    /**
     * Set dynamic HTML attribute value
     *
     * Uses data-attr:attributeName="expression" syntax.
     *
     * @see https://data-star.dev/reference/plugins/attributes
     *
     * @param  string  $attribute  HTML attribute name (e.g., 'href', 'src', 'disabled')
     * @param  string  $expression  JavaScript expression
     */
    public function dataAttr(string $attribute, string $expression): static
    {
        return $this->attr("data-attr:{$attribute}", $expression);
    }

    /**
     * Conditional CSS classes (multiple classes with object syntax)
     *
     * Uses data-class="{className: condition, ...}" syntax.
     *
     * @see https://data-star.dev/reference/plugins/attributes
     *
     * @param  array<string, string>  $classes  Map of className => condition
     */
    public function dataClass(array $classes): static
    {
        $json = json_encode($classes, JSON_THROW_ON_ERROR);

        return $this->attr('data-class', $json);
    }

    /**
     * Conditional CSS class (single class)
     *
     * Uses data-class:className="condition" syntax.
     *
     * @see https://data-star.dev/reference/plugins/attributes
     *
     * @param  string  $className  CSS class name
     * @param  string  $condition  JavaScript expression
     */
    public function dataClassIf(string $className, string $condition): static
    {
        return $this->attr("data-class:{$className}", $condition);
    }

    /**
     * Set dynamic inline style property
     *
     * Uses data-style:property="expression" syntax.
     *
     * @see https://data-star.dev/reference/plugins/attributes
     *
     * @param  string  $property  CSS property name (e.g., 'width', 'opacity', 'backgroundColor')
     * @param  string  $expression  JavaScript expression
     */
    public function dataStyle(string $property, string $expression): static
    {
        return $this->attr("data-style:{$property}", $expression);
    }

    /**
     * Create computed signal (read-only derived state)
     *
     * Uses data-computed:signalName="expression" syntax.
     * Computed signals automatically update when dependencies change.
     *
     * @see https://data-star.dev/reference/plugins/signals
     *
     * @param  string  $name  Computed signal name
     * @param  string  $expression  JavaScript expression to compute value
     */
    public function dataComputed(string $name, string $expression): static
    {
        return $this->attr("data-computed:{$name}", $expression);
    }

    /**
     * Side effect on signal change
     *
     * Runs expression whenever dependencies change.
     *
     * @see https://data-star.dev/reference/plugins/effects
     *
     * @param  string  $expression  JavaScript expression to execute
     */
    public function dataEffect(string $expression): static
    {
        return $this->attr('data-effect', $expression);
    }

    /**
     * Initialize action on element load
     *
     * Executes expression when element initializes (on page load, when patched into DOM,
     * or when the attribute is modified). Useful for fetching initial data or setting up
     * element state.
     *
     * Common use cases:
     * - Fetch initial data when component mounts
     * - Initialize third-party libraries
     * - Set up initial signal values
     * - Trigger actions on dynamic content load
     *
     * Examples:
     * ```php
     * // Fetch data when element loads
     * Html::div()->dataInit('@get("/initial-data")');
     *
     * // Fetch with indicator
     * Html::div()
     *     ->dataIndicator('loading')
     *     ->dataInit('@get("/data")');
     *
     * // Set initial signal value
     * Html::div()->dataInit('$count = 0');
     *
     * // Multiple actions
     * Html::div()->dataInit('@get("/user"); @get("/settings")');
     * ```
     *
     * @see https://data-star.dev/reference/attributes#data-init
     *
     * @param  string  $expression  Expression to execute on initialization
     */
    public function dataInit(string $expression): static
    {
        return $this->attr('data-init', $expression);
    }

    /**
     * React to signal patches (any signal changes)
     *
     * Runs expression whenever ANY signals are patched/updated. Unlike data-effect
     * which tracks specific signal dependencies, this fires on all signal changes.
     *
     * Use cases:
     * - Global state change logging
     * - Syncing state to localStorage
     * - Broadcasting changes to analytics
     * - Triggering side effects on any data change
     *
     * Examples:
     * ```php
     * // Log all signal changes
     * Html::div()->dataOnSignalPatch('console.log("Signals updated:", ctx.signals())');
     *
     * // Save to localStorage on any change
     * Html::div()->dataOnSignalPatch('localStorage.setItem("state", JSON.stringify(ctx.signals()))');
     *
     * // Track analytics
     * Html::div()->dataOnSignalPatch('analytics.track("state_change", ctx.signals())');
     * ```
     *
     * @see https://data-star.dev/reference/attributes#data-on-signal-patch
     *
     * @param  string  $expression  Expression to execute on signal patch
     */
    public function dataOnSignalPatch(string $expression): static
    {
        return $this->attr('data-on-signal-patch', $expression);
    }

    /**
     * Loading indicator signal
     *
     * Automatically creates a boolean signal that becomes true during fetch
     * actions and false when they complete. The signal is scoped to this
     * element and its children.
     *
     * **How it works:**
     * - Signal is initially false
     * - Set to true when ANY fetch action starts within this element
     * - Set to false when fetch completes (success, error, or cancelled)
     * - Automatically reset to false if element is removed from DOM
     * - No manual state management needed
     *
     * **Common use cases:**
     * - Disable buttons during save operations
     * - Show loading spinners during data fetches
     * - Display progress indicators during form submissions
     * - Prevent duplicate submissions
     *
     * Examples:
     * ```php
     * // Disable button while saving
     * Html::button()
     *     ->dataIndicator('saving')
     *     ->postx('/save')
     *     ->dataAttr('disabled', '$saving')
     *     ->content(
     *         Html::span()->dataShow('!$saving')->text('Save'),
     *         Html::span()->dataShow('$saving')->text('Saving...')
     *     );
     *
     * // Show spinner during fetch
     * Html::div()
     *     ->dataIndicator('loading')
     *     ->get('/data')
     *     ->content(
     *         Html::div()->dataShow('$loading')->text('Loading...'),
     *         Html::div()->dataShow('!$loading')->content(...)
     *     );
     *
     * // Form with loading state
     * Html::form()
     *     ->dataIndicator('submitting')
     *     ->postx('/submit')
     *     ->content(
     *         Html::input()->name('email'),
     *         Html::button()
     *             ->type('submit')
     *             ->dataAttr('disabled', '$submitting')
     *             ->text('Submit')
     *     );
     * ```
     *
     * @see https://data-star.dev/reference/plugins/backend#data-indicator
     *
     * @param  string  $signalName  Name of boolean signal to create (e.g., 'loading', 'saving', 'submitting')
     */
    public function dataIndicator(string $signalName): static
    {
        return $this->attr('data-indicator', $signalName);
    }

    /**
     * Element reference
     *
     * Creates a reference to this element accessible via $refs.
     *
     * @see https://data-star.dev/reference/plugins/refs
     *
     * @param  string  $name  Reference name
     */
    public function dataRef(string $name): static
    {
        return $this->attr('data-ref', $name);
    }

    /**
     * Execute action when element intersects viewport (IntersectionObserver)
     *
     * Modifiers:
     * - once: Trigger only once
     * - half: Trigger at 50% visibility
     * - full: Trigger at 100% visibility
     * - threshold.{0-1}: Custom visibility threshold (e.g., threshold.0.75)
     * - rootMargin.{value}: Root margin (e.g., rootMargin.100px)
     *
     * @see https://data-star.dev/reference/plugins/intersect
     *
     * @param  string  $expression  Action to execute
     * @param  array<string>  $modifiers  Modifier array (e.g., ['once', 'half'])
     */
    public function dataOnIntersect(string $expression, array $modifiers = []): static
    {
        $attrName = 'data-on-intersect';

        if (! empty($modifiers)) {
            $attrName .= '__'.implode('__', $modifiers);
        }

        return $this->attr($attrName, $expression);
    }

    /**
     * Execute action at intervals
     *
     * Modifiers:
     * - duration.{time}ms: Set interval duration (default: 1000ms)
     * - leading: Execute immediately before first interval
     *
     * @see https://data-star.dev/reference/plugins/interval
     *
     * @param  string  $expression  Action to execute
     * @param  array<string>  $modifiers  Modifier array (e.g., ['duration.5000ms', 'leading'])
     */
    public function dataOnInterval(string $expression, array $modifiers = []): static
    {
        $attrName = 'data-on-interval';

        if (! empty($modifiers)) {
            $attrName .= '__'.implode('__', $modifiers);
        }

        return $this->attr($attrName, $expression);
    }

    /**
     * Intersection observer (deprecated - use dataOnIntersect instead)
     *
     * @deprecated Use dataOnIntersect() for full modifier support
     *
     * @param  string  $expression  JavaScript expression to execute on intersection
     */
    public function dataIntersect(string $expression): static
    {
        return $this->dataOnIntersect($expression);
    }
}
