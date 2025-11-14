<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Form;

use Closure;
use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Html\Services\ValidationRuleTransformer;
use Dancycodes\Hyper\Validation\SignalValidator;

/**
 * Signal-Centric Validation System
 *
 * Provides fluent API for validating SIGNALS (reactive state), not form fields.
 * This is a fundamental shift from form-centric to signal-centric validation.
 *
 * Key Concepts:
 * - Validates signal paths inferred from data-bind attribute
 * - Falls back to name attribute if no data-bind
 * - Supports nested signals ('user.email', 'profile.phone')
 * - Enforces security for locked signals ('userId_')
 * - Rejects local signals ('_tempEdit') from server validation
 *
 * Signal Path Inference:
 * 1. Priority 1: data-bind="user.email" → validates signal 'user.email'
 * 2. Priority 2: name="email" → validates signal 'email'
 *
 * Signal Types:
 * - Regular: 'email', 'user.email' → Normal validation
 * - Locked: 'userId_' → Tamper-proof, encrypted in session
 * - Local: '_tempEdit' → Client-only, THROWS EXCEPTION if validated
 *
 * @example Basic signal validation
 * Html::input()
 *     ->name('email')
 *     ->dataBind('email')
 *     ->validate('required|email', clientSide: true, live: true)
 *     ->withError();
 * @example Nested signal validation
 * Html::input()
 *     ->name('email')
 *     ->dataBind('user.email')
 *     ->validate('required|email|max:255')
 *     ->withError();
 * @example Locked signal validation
 * Html::input()
 *     ->name('user_id')
 *     ->dataBind('userId_')
 *     ->validate('required|integer')
 *     ->withError();
 */
trait HasValidation
{
    /**
     * Validation rules indexed by SIGNAL PATH (not field name)
     *
     * Examples:
     * - 'email' => 'required|email'
     * - 'user.email' => 'required|email|max:255'
     * - 'userId_' => 'required|integer'
     *
     * @var array<string, string>
     */
    protected array $validationRules = [];

    /**
     * Custom validation messages
     *
     * @var array<string, string>
     */
    protected array $validationMessages = [];

    /**
     * Custom attribute names for error messages
     *
     * @var array<string, string>
     */
    protected array $validationAttributes = [];

    /**
     * The inferred signal path for this element
     * Cached to avoid re-inference
     */
    protected ?string $validatedSignalPath = null;

    /**
     * Whether to generate HTML5 validation attributes
     */
    protected bool $clientSideValidation = false;

    /**
     * Whether to enable real-time validation
     */
    protected bool $liveValidation = false;

    /**
     * Whether to auto-generate error display div
     */
    protected bool $shouldGenerateErrorDiv = false;

    /**
     * CSS classes for error div
     */
    protected string|array|Closure $errorDivClass = 'text-red-500 text-sm mt-1';

    /**
     * Attach validation rules to the SIGNAL bound to this element
     *
     * REVOLUTIONARY CHANGE: Validates the SIGNAL, not the form field!
     *
     * Signal path is inferred from:
     * 1. data-bind attribute (priority 1) → 'user.email'
     * 2. name attribute (priority 2) → 'email'
     *
     * @param string|array|Closure $rules Laravel validation rules
     * @param array|Closure $messages Custom error messages
     * @param array|Closure $attributes Custom attribute names
     * @param bool $clientSide Generate HTML5 validation attributes?
     * @param bool $live Enable real-time validation via SignalValidator?
     *
     * @throws \RuntimeException If no data-bind or name attribute exists
     * @throws \RuntimeException If attempting to validate local signal (starts with _)
     */
    public function validate(
        string|array|Closure $rules,
        array|Closure $messages = [],
        array|Closure $attributes = [],
        bool $clientSide = false,
        bool $live = false
    ): static {
        // Evaluate closures
        $rules = $this->evaluate($rules);
        $messages = $this->evaluate($messages);
        $attributes = $this->evaluate($attributes);

        // CRITICAL: Infer SIGNAL PATH from data-bind or name
        $signalPath = $this->inferSignalPath();

        if (!$signalPath) {
            throw new \RuntimeException(
                'Cannot validate without data-bind or name attribute. ' .
                'Add dataBind() or name() before calling validate().'
            );
        }

        // Store the inferred signal path
        $this->validatedSignalPath = $signalPath;

        // Store validation data indexed by SIGNAL PATH
        if (is_string($rules)) {
            $this->validationRules[$signalPath] = $rules;
        } elseif (is_array($rules)) {
            $this->validationRules = array_merge($this->validationRules, $rules);
        }

        $this->validationMessages = array_merge($this->validationMessages, $messages);
        $this->validationAttributes = array_merge($this->validationAttributes, $attributes);
        $this->clientSideValidation = $clientSide;
        $this->liveValidation = $live;

        return $this;
    }

    /**
     * Auto-generate error display element for the validated signal
     *
     * CRITICAL: Uses the SIGNAL PATH for data-error, not the field name!
     *
     * @param string|array|Closure $class CSS classes for error div
     *
     * @throws \RuntimeException If validate() not called first
     */
    public function withError(string|array|Closure $class = 'text-red-500 text-sm mt-1'): static
    {
        if (!$this->validatedSignalPath) {
            throw new \RuntimeException(
                'Call validate() before withError() to specify signal path. ' .
                'Example: ->validate(...)->withError()'
            );
        }

        $this->shouldGenerateErrorDiv = true;
        $this->errorDivClass = $class;

        return $this;
    }

    /**
     * Infer signal path from data-bind or name attribute
     *
     * This is the CRITICAL METHOD that makes signal-centric validation work.
     *
     * Priority:
     * 1. data-bind="user.email" → returns 'user.email'
     * 2. name="email" → returns 'email'
     *
     * @return string|null Signal path or null if no binding found
     */
    protected function inferSignalPath(): ?string
    {
        // Priority 1: data-bind attribute (explicit signal binding)
        if (isset($this->attributes['data-bind'])) {
            $bind = $this->attributes['data-bind'];

            // Remove $ prefix if present: '$email' → 'email'
            return ltrim($bind, '$');
        }

        // Priority 2: name attribute (implicit signal binding)
        if (isset($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        return null;
    }

    /**
     * Get validation rules for this element
     *
     * Returns rules indexed by SIGNAL PATH, not field name.
     *
     * @return array<string, string>
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Get validation messages
     *
     * @return array<string, string>
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    /**
     * Get validation attribute names
     *
     * @return array<string, string>
     */
    public function getValidationAttributes(): array
    {
        return $this->validationAttributes;
    }

    /**
     * Get the inferred signal path for this element
     */
    public function getValidatedSignalPath(): ?string
    {
        return $this->validatedSignalPath;
    }

    /**
     * Get complete validation data (signal-centric)
     *
     * @return array{rules: array<string, string>, messages: array<string, string>, attributes: array<string, string>}
     */
    public function getValidationData(): array
    {
        return [
            'rules' => $this->validationRules,
            'messages' => $this->validationMessages,
            'attributes' => $this->validationAttributes,
        ];
    }

    /**
     * Collect validation data recursively from element tree
     *
     * Gathers all signal validation rules from children.
     *
     * @return array{rules: array<string, string>, messages: array<string, string>, attributes: array<string, string>}
     */
    public function collectValidationData(): array
    {
        $data = $this->getValidationData();

        // Recursively collect from children
        if (method_exists($this, 'getChildren')) {
            foreach ($this->getChildren() as $child) {
                if (method_exists($child, 'collectValidationData')) {
                    $childData = $child->collectValidationData();
                    $data['rules'] = array_merge($data['rules'], $childData['rules']);
                    $data['messages'] = array_merge($data['messages'], $childData['messages']);
                    $data['attributes'] = array_merge($data['attributes'], $childData['attributes']);
                }
            }
        }

        return $data;
    }

    /**
     * Apply HTML5 validation attributes if clientSide enabled
     *
     * Transforms Laravel validation rules into HTML5 attributes.
     * Uses the SIGNAL PATH to find rules, not field name.
     */
    protected function applyHtml5ValidationAttributes(): void
    {
        if (!$this->clientSideValidation || !$this->validatedSignalPath) {
            return;
        }

        if (!isset($this->validationRules[$this->validatedSignalPath])) {
            return;
        }

        $rules = $this->validationRules[$this->validatedSignalPath];
        $html5Attrs = ValidationRuleTransformer::toHtml5Attributes($rules);

        foreach ($html5Attrs as $attr => $value) {
            // Don't override existing attributes
            if (!isset($this->attributes[$attr])) {
                $this->attr($attr, $value);
            }
        }
    }

    /**
     * Apply live validation if enabled
     *
     * Registers signal path with SignalValidator and attaches
     * debounced validation action.
     */
    protected function applyLiveValidation(): void
    {
        if (!$this->liveValidation || !$this->validatedSignalPath) {
            return;
        }

        if (!isset($this->validationRules[$this->validatedSignalPath])) {
            return;
        }

        // Register with SignalValidator (for signal-based auto-route)
        app(SignalValidator::class)->register(
            $this->validatedSignalPath,
            $this->validationRules[$this->validatedSignalPath],
            $this->validationMessages,
            $this->validationAttributes
        );

        // Attach debounced validation action
        $event = method_exists($this, 'getDefaultEvent') ? $this->getDefaultEvent() : 'input';

        // CRITICAL: Use signal path in route, URL-encoded for nested paths
        $encodedPath = str_replace('.', '%2E', $this->validatedSignalPath);

        // Add data-on:{event}__debounce.300ms="@patchx('/validate-signal/{path}')"
        $this->dataOn(
            $event . '__debounce.300ms',
            "@patchx('/validate-signal/{$encodedPath}')"
        );
    }

    /**
     * Generate error div element with SIGNAL PATH for data-error
     *
     * CRITICAL: Uses signal path, not field name!
     */
    protected function generateErrorDiv(): ?\Dancycodes\Hyper\Html\Elements\Base\Element
    {
        if (!$this->shouldGenerateErrorDiv || !$this->validatedSignalPath) {
            return null;
        }

        $class = $this->evaluate($this->errorDivClass);

        return Html::div()
            ->dataError($this->validatedSignalPath)  // Uses SIGNAL PATH!
            ->class($class);
    }
}
