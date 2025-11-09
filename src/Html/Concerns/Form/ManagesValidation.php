<?php

namespace Dancycodes\Hyper\Html\Concerns\Form;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasValidation;

/**
 * Form-level validation management
 *
 * Provides orchestration for multi-field validation, auto-generation
 * of error displays, signal initialization, and validation groups for
 * multi-step forms.
 *
 * @example
 * Html::form()
 *     ->withSignals()
 *     ->withErrors()
 *     ->postx('/submit')
 *     ->content(
 *         Html::input()->name('email')->validate('required|email'),
 *         Html::input()->name('password')->validate('required|min:8')
 *     );
 */
trait ManagesValidation
{
    use HasValidation;

    /**
     * Whether to auto-generate error display elements for all validated inputs
     */
    protected bool $autoGenerateErrors = false;

    /**
     * Whether to auto-inject @signals with errors array
     */
    protected bool $autoGenerateSignals = false;

    /**
     * Validation groups for multi-step forms
     *
     * @var array<string, array<int, string>>
     */
    protected array $validationGroups = [];

    /**
     * Auto-generate error display elements for all validated inputs
     *
     * @param  string|array|Closure  $class  CSS classes for error divs
     */
    public function withErrors(string|array|Closure $class = 'text-red-500 text-sm mt-1'): static
    {
        $this->autoGenerateErrors = true;
        $this->errorDivClass = $class;

        return $this;
    }

    /**
     * Auto-inject @signals with errors array and validated field signals
     *
     * Automatically creates signals for:
     * - errors: [] (for storing validation errors)
     * - Each validated field: '' (empty string initial value)
     */
    public function withSignals(): static
    {
        $this->autoGenerateSignals = true;

        return $this;
    }

    /**
     * Define validation group for multi-step forms
     *
     * @param  string  $name  Group name (e.g., 'step1', 'personalInfo')
     * @param  array<int, string>  $fieldNames  Field names in this group
     */
    public function validationGroup(string $name, array $fieldNames): static
    {
        $this->validationGroups[$name] = $fieldNames;

        return $this;
    }

    /**
     * Get validation rules (optionally filtered by group)
     *
     * @param  string|null  $group  Get rules for specific group only
     * @return array<string, string>
     */
    public function getValidationRules(?string $group = null): array
    {
        $allData = $this->collectValidationData();

        if ($group !== null && isset($this->validationGroups[$group])) {
            // Filter by group
            return array_intersect_key(
                $allData['rules'],
                array_flip($this->validationGroups[$group])
            );
        }

        return $allData['rules'];
    }

    /**
     * Inject validation setup during rendering
     *
     * Follows injectIcons() pattern from HasIcons trait.
     * Called automatically before rendering attributes.
     */
    protected function injectValidationSetup(): void
    {
        // 1. Auto-inject signals if enabled
        if ($this->autoGenerateSignals) {
            $this->injectValidationSignals();
        }

        // 2. Auto-generate error divs if enabled
        if ($this->autoGenerateErrors) {
            $this->injectErrorDivs();
        }
    }

    /**
     * Inject validation signals (@signals attribute)
     *
     * Creates signals for:
     * - errors: [] (validation error storage)
     * - Each validated field: '' (field value storage)
     */
    protected function injectValidationSignals(): void
    {
        $signals = ['errors' => []];

        // Add empty signal for each validated field
        foreach ($this->getValidationRules() as $fieldName => $rules) {
            $signals[$fieldName] = '';
        }

        $this->dataSignals($signals);
    }

    /**
     * Inject error divs after each validated input
     *
     * Recursively walks children and adds withError() to inputs with validation rules.
     */
    protected function injectErrorDivs(): void
    {
        $this->walkChildren(function ($child) {
            if (method_exists($child, 'validate') && ! empty($child->getValidationRules())) {
                $child->withError($this->errorDivClass);
            }
        });
    }

    /**
     * Walk children recursively and apply callback
     *
     * @param  Closure  $callback  Callback to apply to each child
     */
    protected function walkChildren(Closure $callback): void
    {
        if (! method_exists($this, 'getChildren')) {
            return;
        }

        foreach ($this->getChildren() as $child) {
            $callback($child);

            if (method_exists($child, 'walkChildren')) {
                $child->walkChildren($callback);
            }
        }
    }
}
