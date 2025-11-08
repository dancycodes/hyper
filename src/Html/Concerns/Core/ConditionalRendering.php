<?php

namespace Dancycodes\Hyper\Html\Concerns\Core;

use Closure;

/**
 * Helper methods for conditional rendering
 *
 * Supports dynamic conditions via closures with dependency injection.
 */
trait ConditionalRendering
{
    /**
     * Execute callback if condition is true
     *
     * The condition can be a closure that will be evaluated with dependency
     * injection before the truthiness check.
     *
     * Example:
     * ```php
     * Html::button('Delete')
     *     ->when(fn() => auth()->user()->can('delete'), function ($btn) {
     *         return $btn->class('btn-danger');
     *     });
     * ```
     *
     * @param mixed $condition Boolean, closure returning boolean, or any truthy value
     * @param callable $callback Callback receiving the element if condition is true
     */
    public function when(mixed $condition, callable $callback): static
    {
        // 1. Evaluate condition if it's a closure (supports dependency injection)
        if ($condition instanceof Closure) {
            $condition = $this->evaluate($condition);
        }

        // 2. Execute callback if condition is truthy
        if ($condition) {
            return $callback($this);
        }

        // 3. Return $this unchanged if condition is false
        return $this;
    }

    /**
     * Execute callback if condition is false
     *
     * The condition can be a closure that will be evaluated with dependency
     * injection before the truthiness check.
     *
     * Example:
     * ```php
     * Html::div()
     *     ->unless(fn() => auth()->check(), function ($div) {
     *         return $div->content('Please log in');
     *     });
     * ```
     *
     * @param mixed $condition Boolean, closure returning boolean, or any truthy value
     * @param callable $callback Callback receiving the element if condition is false
     */
    public function unless(mixed $condition, callable $callback): static
    {
        // 1. Evaluate condition if it's a closure (supports dependency injection)
        if ($condition instanceof Closure) {
            $condition = $this->evaluate($condition);
        }

        // 2. Execute callback if condition is falsy
        if (!$condition) {
            return $callback($this);
        }

        // 3. Return $this unchanged if condition is true
        return $this;
    }
}
