<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Form;

use Closure;
use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Html\Services\FormValidationRegistry;
use Dancycodes\Hyper\Html\Services\ValidationRuleTransformer;

/**
 * Validation attributes and Laravel validation integration
 *
 * Provides fluent API for attaching Laravel validation rules to HTML elements
 * with automatic error display, HTML5 attribute generation, and real-time validation.
 *
 * Compatible with Laravel's validate() signature:
 * - validate($rules, $messages, $attributes)
 *
 * Plus HTML builder extensions:
 * - clientSide: Generate HTML5 validation attributes
 * - live: Enable real-time validation on input change
 *
 * @example
 * Html::input()
 *     ->name('email')
 *     ->validate('required|email', clientSide: true, live: true)
 *     ->withError();
 */
trait HasValidation
{
    /**
     * Validation rules indexed by field name
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
     * Attach Laravel validation rules to this element
     *
     * Signature compatible with Laravel's validate() and signals()->validate():
     * - $rules: Validation rules (string for single field, array for multiple)
     * - $messages: Custom error messages
     * - $attributes: Custom attribute names for error messages
     *
     * Plus HTML builder extensions:
     * - clientSide: Generate HTML5 validation attributes (required, pattern, etc.)
     * - live: Enable real-time validation on input change
     *
     * @param  string|array|Closure  $rules  Laravel validation rules
     * @param  array|Closure  $messages  Custom error messages
     * @param  array|Closure  $attributes  Custom attribute names
     * @param  bool  $clientSide  Generate HTML5 validation attributes?
     * @param  bool  $live  Enable real-time validation?
     */
    public function validate(
        string|array|Closure $rules,
        array|Closure $messages = [],
        array|Closure $attributes = [],
        bool $clientSide = false,
        bool $live = false
    ): static {
        // Evaluate closures (follows Hyper's EvaluatesClosures pattern)
        $rules = $this->evaluate($rules);
        $messages = $this->evaluate($messages);
        $attributes = $this->evaluate($attributes);

        // Get field name from 'name' attribute
        $fieldName = $this->attributes['name'] ?? null;

        // Store validation data
        if (is_string($rules) && $fieldName) {
            // Single field validation (string rules)
            $this->validationRules[$fieldName] = $rules;
        } elseif (is_array($rules)) {
            // Multiple field validation (array rules)
            $this->validationRules = array_merge($this->validationRules, $rules);
        }

        $this->validationMessages = array_merge($this->validationMessages, $messages);
        $this->validationAttributes = array_merge($this->validationAttributes, $attributes);
        $this->clientSideValidation = $clientSide;
        $this->liveValidation = $live;

        return $this;
    }

    /**
     * Auto-generate error display element after this element
     *
     * @param  string|array|Closure  $class  CSS classes for error div
     */
    public function withError(string|array|Closure $class = 'text-red-500 text-sm mt-1'): static
    {
        $this->shouldGenerateErrorDiv = true;
        $this->errorDivClass = $class;

        return $this;
    }

    /**
     * Get validation rules for this element
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
     * Get complete validation data (Laravel-compatible)
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
     * Called by Form to gather all validation rules from children.
     * Follows the DOM inspection pattern from HasIcons trait.
     *
     * @return array{rules: array<string, string>, messages: array<string, string>, attributes: array<string, string>}
     */
    public function collectValidationData(): array
    {
        $data = $this->getValidationData();

        // Recursively collect from children (follows HasIcons pattern)
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
     * Called during rendering (follows injectIcons pattern from HasIcons).
     * Transforms Laravel validation rules into HTML5 attributes.
     */
    protected function applyHtml5ValidationAttributes(): void
    {
        if (! $this->clientSideValidation) {
            return;
        }

        $fieldName = $this->attributes['name'] ?? null;
        if (! $fieldName || ! isset($this->validationRules[$fieldName])) {
            return;
        }

        $rules = $this->validationRules[$fieldName];
        $html5Attrs = ValidationRuleTransformer::toHtml5Attributes($rules);

        foreach ($html5Attrs as $attr => $value) {
            // Don't override existing attributes
            if (! isset($this->attributes[$attr])) {
                $this->attr($attr, $value);
            }
        }
    }

    /**
     * Apply live validation if enabled
     *
     * Automatically attaches debounced validation action and registers
     * with FormValidationRegistry for auto-route handling.
     */
    protected function applyLiveValidation(): void
    {
        if (! $this->liveValidation) {
            return;
        }

        $fieldName = $this->attributes['name'] ?? null;
        if (! $fieldName || ! isset($this->validationRules[$fieldName])) {
            return;
        }

        // Register with FormValidationRegistry (for auto-route)
        app(FormValidationRegistry::class)->register(
            $fieldName,
            $this->validationRules[$fieldName],
            $this->validationMessages,
            $this->validationAttributes
        );

        // Attach debounced validation action (follows smart event detection from HasActionMethods)
        $event = method_exists($this, 'getDefaultEvent') ? $this->getDefaultEvent() : 'input';

        // Add data-on:{event}__debounce.300ms="@patchx('/validate/{field}')"
        $this->dataOn(
            $event.'__debounce.300ms',
            "@patchx('/validate/{$fieldName}')"
        );
    }

    /**
     * Generate error div element
     *
     * Creates a div with data-error attribute for displaying validation errors.
     */
    protected function generateErrorDiv(): ?\Dancycodes\Hyper\Html\Elements\Base\Element
    {
        if (! $this->shouldGenerateErrorDiv) {
            return null;
        }

        $fieldName = $this->attributes['name'] ?? null;
        if (! $fieldName) {
            return null;
        }

        $class = $this->evaluate($this->errorDivClass);

        return Html::div()
            ->dataError($fieldName)
            ->class($class);
    }
}
