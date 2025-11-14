<?php

namespace Dancycodes\Hyper\Html\Concerns\Form;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasValidation;

/**
 * Form-level Signal Validation Management
 *
 * Provides orchestration for multi-signal validation, auto-generation
 * of error displays, signal initialization, and validation groups for
 * multi-step forms.
 *
 * SIGNAL-CENTRIC APPROACH: Manages validation for signals, not form fields.
 *
 * @example Basic form with signal validation
 * Html::form()
 *     ->withSignals()  // Auto-injects errors signal + validated signal paths
 *     ->withErrors()   // Auto-generates error divs for all validated inputs
 *     ->postx('/submit')
 *     ->content(
 *         Html::input()->name('email')->dataBind('email')->validate('required|email'),
 *         Html::input()->name('password')->dataBind('password')->validate('required|min:8')
 *     );
 * @example Nested signals
 * Html::form()
 *     ->withSignals()
 *     ->withErrors()
 *     ->content(
 *         Html::input()->dataBind('user.email')->validate('required|email'),
 *         Html::input()->dataBind('user.profile.bio')->validate('max:500')
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
     * Whether to auto-inject @signals with errors array and validated signal paths
     */
    protected bool $autoGenerateSignals = false;

    /**
     * Validation groups for multi-step forms
     *
     * Maps group names to signal paths (not field names).
     *
     * @var array<string, array<int, string>>
     */
    protected array $validationGroups = [];

    /**
     * Auto-generate error display elements for all validated inputs
     *
     * @param string|array|Closure $class CSS classes for error divs
     */
    public function withErrors(string|array|Closure $class = 'text-red-500 text-sm mt-1'): static
    {
        $this->autoGenerateErrors = true;
        $this->errorDivClass = $class;

        return $this;
    }

    /**
     * Auto-inject @signals with errors array and validated signal paths
     *
     * SIGNAL-CENTRIC: Creates signals for signal paths, not field names.
     *
     * Automatically creates signals for:
     * - errors: [] (for storing validation errors)
     * - Each validated signal path: '' (empty string initial value)
     *
     * Examples:
     * - 'email' signal for simple input
     * - 'user.email' signal for nested input
     * - 'userId_' signal for locked input (NOT '_tempEdit' - local signals rejected)
     */
    public function withSignals(): static
    {
        $this->autoGenerateSignals = true;

        return $this;
    }

    /**
     * Define validation group for multi-step forms
     *
     * SIGNAL-CENTRIC: Groups are indexed by signal paths, not field names.
     *
     * @param string $name Group name (e.g., 'step1', 'personalInfo')
     * @param array<int, string> $signalPaths Signal paths in this group
     *
     * @example
     * ->validationGroup('step1', ['email', 'name'])
     * ->validationGroup('step2', ['user.profile.bio', 'userId_'])
     */
    public function validationGroup(string $name, array $signalPaths): static
    {
        $this->validationGroups[$name] = $signalPaths;

        return $this;
    }

    /**
     * Get validation rules (optionally filtered by group)
     *
     * Returns rules indexed by SIGNAL PATH, not field name.
     *
     * @param string|null $group Get rules for specific group only
     *
     * @return array<string, string> Rules indexed by signal path
     */
    public function getValidationRules(?string $group = null): array
    {
        $allData = $this->collectValidationData();

        if ($group !== null && isset($this->validationGroups[$group])) {
            // Filter by group (signal paths)
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
     * SIGNAL-CENTRIC: Creates signals for signal paths, not field names.
     *
     * Creates signals for:
     * - errors: [] (validation error storage)
     * - Each validated signal path: '' (signal value storage)
     *
     * Examples:
     * - 'email' => ''
     * - 'user.email' => '' (nested signal created automatically)
     * - 'userId_' => '' (locked signal)
     */
    protected function injectValidationSignals(): void
    {
        $signals = ['errors' => []];

        // Add empty signal for each validated signal path
        foreach ($this->getValidationRules() as $signalPath => $rules) {
            $signals[$signalPath] = '';
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
            if (method_exists($child, 'validate') && !empty($child->getValidationRules())) {
                $child->withError($this->errorDivClass);
            }
        });
    }

    /**
     * Walk children recursively and apply callback
     *
     * @param Closure $callback Callback to apply to each child
     */
    protected function walkChildren(Closure $callback): void
    {
        if (!method_exists($this, 'getChildren')) {
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
