<?php

namespace Dancycodes\Hyper\Html\Concerns\Actions;

/**
 * Simplified action methods for Datastar HTTP actions
 *
 * Provides convenient methods for Datastar's backend actions with smart
 * event detection based on element type. Reduces verbosity while maintaining
 * full access to Datastar's capabilities.
 *
 * Smart Event Defaults:
 * - form elements: 'submit__prevent' (prevents default form submission)
 * - button/a elements: 'click'
 * - input/select/textarea elements: 'input'
 * - all other elements: 'click'
 *
 * Event Modifiers:
 * Event modifiers can be added directly to the $event parameter using
 * Datastar's double underscore syntax: 'click__prevent', 'input__debounce.300ms'
 *
 * Examples:
 * ```php
 * // Simple POST with auto-event detection
 * Html::button()->postx('/save')  // Uses 'click' event
 * Html::form()->postx('/submit')   // Uses 'submit__prevent' event
 *
 * // Explicit event with modifiers
 * Html::div()->get('/refresh', 'click__window')
 * Html::input()->get('/search', 'input__debounce.300ms')
 *
 * // With options
 * Html::button()->postx('/save', null, [
 *     'headers' => ['X-Custom' => 'value'],
 *     'retry' => 3
 * ])
 *
 * // Navigation with key
 * Html::a()->navigate('/dashboard', null, 'main-content')
 * ```
 *
 * @see https://data-star.dev/reference/plugins/backend
 */
trait HasActionMethods
{
    /**
     * CSRF-protected POST action (Hyper extension)
     *
     * Performs a POST request with automatic CSRF token inclusion.
     * Use this for any mutating operation that creates resources.
     *
     * @param  string  $url  URL to POST to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function postx(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('postx', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * CSRF-protected PUT action (Hyper extension)
     *
     * Performs a PUT request with automatic CSRF token inclusion.
     * Use this for full resource updates.
     *
     * @param  string  $url  URL to PUT to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function putx(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('putx', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * CSRF-protected PATCH action (Hyper extension)
     *
     * Performs a PATCH request with automatic CSRF token inclusion.
     * Use this for partial resource updates.
     *
     * @param  string  $url  URL to PATCH to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function patchx(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('patchx', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * CSRF-protected DELETE action (Hyper extension)
     *
     * Performs a DELETE request with automatic CSRF token inclusion.
     * Use this for resource deletion.
     *
     * @param  string  $url  URL to DELETE to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function deletex(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('deletex', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * GET action (Datastar core)
     *
     * Performs a GET request to fetch data or HTML.
     * Use this for read-only operations.
     *
     * @param  string  $url  URL to GET from
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function get(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('get', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * POST action (Datastar core)
     *
     * Performs a POST request WITHOUT CSRF protection.
     * Use postx() instead for Laravel applications.
     *
     * @param  string  $url  URL to POST to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function post(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('post', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * PUT action (Datastar core)
     *
     * Performs a PUT request WITHOUT CSRF protection.
     * Use putx() instead for Laravel applications.
     *
     * @param  string  $url  URL to PUT to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function put(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('put', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * PATCH action (Datastar core)
     *
     * Performs a PATCH request WITHOUT CSRF protection.
     * Use patchx() instead for Laravel applications.
     *
     * @param  string  $url  URL to PATCH to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function patch(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('patch', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * DELETE action (Datastar core)
     *
     * Performs a DELETE request WITHOUT CSRF protection.
     * Use deletex() instead for Laravel applications.
     *
     * @param  string  $url  URL to DELETE to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function delete(string $url, ?string $event = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();
        $action = $this->buildActionString('delete', $url, $options);

        return $this->dataOn($event, $action);
    }

    /**
     * Navigate action (Hyper extension)
     *
     * Performs client-side navigation with optional targeted updates.
     * Updates the browser URL and fetches new content without full page reload.
     *
     * @param  string  $url  URL to navigate to
     * @param  string|null  $event  Event name (null = smart default based on element type)
     * @param  string|null  $key  Optional navigation key for targeted updates
     * @param  array<string, mixed>  $options  Datastar fetch options (headers, retry, etc.)
     */
    public function navigate(string $url, ?string $event = null, ?string $key = null, array $options = []): static
    {
        $event = $event ?? $this->getDefaultEvent();

        // Build action string with optional key parameter
        if ($key !== null) {
            $action = $this->buildNavigateActionString($url, $key, $options);
        } else {
            $action = $this->buildActionString('navigate', $url, $options);
        }

        return $this->dataOn($event, $action);
    }

    /**
     * Dispatch custom event (Hyper extension)
     *
     * Dispatches a custom JavaScript event with optional detail data.
     * Useful for component communication and decoupled architectures.
     *
     * @param  string  $eventName  Custom event name to dispatch
     * @param  string|null  $event  DOM event that triggers dispatch (null = smart default)
     * @param  array<string, mixed>  $detail  Optional event detail data
     */
    public function dispatch(string $eventName, ?string $event = null, array $detail = []): static
    {
        $event = $event ?? $this->getDefaultEvent();

        // Build action string: @dispatch('eventName') or @dispatch('eventName', {detail})
        if (empty($detail)) {
            $action = "@dispatch('{$eventName}')";
        } else {
            $detailJson = json_encode(
                $detail,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
            );
            $action = "@dispatch('{$eventName}', {$detailJson})";
        }

        return $this->dataOn($event, $action);
    }

    /**
     * Get smart default event based on element type and attributes
     *
     * Returns appropriate default event for the current element:
     * - form: 'submit__prevent' (prevents default form submission)
     * - button, a: 'click'
     * - input[type=checkbox|radio|file|color]: 'change' (fires on state change)
     * - input (other types): 'input' (fires on every keystroke/change)
     * - select: 'change' (fires when option selected)
     * - textarea: 'input' (fires on every keystroke)
     * - default: 'click'
     *
     * Rationale:
     * - Checkboxes/radios fire 'change' when toggled, not 'input'
     * - File inputs fire 'change' when file selected
     * - Color pickers fire 'change' when color selected
     * - Text-like inputs fire 'input' for real-time feedback
     * - Select elements fire 'change' when selection changes
     */
    protected function getDefaultEvent(): string
    {
        return match ($this->tag) {
            'form' => 'submit__prevent',
            'button', 'a' => 'click',
            'input' => $this->getInputDefaultEvent(),
            'select' => 'change',
            'textarea' => 'input',
            default => 'click'
        };
    }

    /**
     * Get smart default event for input elements based on type attribute
     *
     * Input types that use 'change' event:
     * - checkbox, radio: Fire when checked/unchecked
     * - file: Fire when file selected
     * - color: Fire when color selected
     *
     * Input types that use 'input' event:
     * - text, email, password, url, tel, search: Fire on every keystroke
     * - number, date, time, datetime-local: Fire on every change
     * - range: Fire as slider moves
     * - All other types default to 'input'
     */
    protected function getInputDefaultEvent(): string
    {
        // Get the type attribute if set
        $type = $this->attributes['type'] ?? 'text';

        // Types that should use 'change' event
        return match ($type) {
            'checkbox', 'radio', 'file', 'color' => 'change',
            default => 'input'
        };
    }

    /**
     * Build Datastar action string from components
     *
     * Generates properly formatted Datastar action with URL and options:
     * - No options: @action('/url')
     * - With options: @action('/url', {options})
     *
     * @param  string  $action  Action name (get, postx, navigate, etc.)
     * @param  string  $url  Target URL
     * @param  array<string, mixed>  $options  Datastar fetch options
     */
    protected function buildActionString(string $action, string $url, array $options): string
    {
        // No options - simple format
        if (empty($options)) {
            return "@{$action}('{$url}')";
        }

        // With options - JSON encode with XSS protection
        $optionsJson = json_encode(
            $options,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );

        return "@{$action}('{$url}', {$optionsJson})";
    }

    /**
     * Build navigate action string with key parameter
     *
     * Generates navigate action with optional key for targeted updates:
     * - No options: @navigate('/url', 'key')
     * - With options: @navigate('/url', 'key', {options})
     *
     * @param  string  $url  Target URL
     * @param  string  $key  Navigation key for targeted updates
     * @param  array<string, mixed>  $options  Datastar fetch options
     */
    protected function buildNavigateActionString(string $url, string $key, array $options): string
    {
        // No options - format with key only
        if (empty($options)) {
            return "@navigate('{$url}', '{$key}')";
        }

        // With options - JSON encode with XSS protection
        $optionsJson = json_encode(
            $options,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );

        return "@navigate('{$url}', '{$key}', {$optionsJson})";
    }
}
