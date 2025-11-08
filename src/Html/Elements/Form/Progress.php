<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Progress Element
 *
 * Represents the completion progress of a task.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-progress-element
 */
class Progress extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('progress');
    }

    /**
     * Set the value attribute (current progress)
     *
     * @param float|Closure $value Progress value or closure
     */
    public function value(float|Closure $value): static
    {
        return $this->attr('value', $value);
    }

    /**
     * Set the max attribute (maximum value)
     *
     * @param float|Closure $max Maximum value or closure
     */
    public function max(float|Closure $max): static
    {
        return $this->attr('max', $max);
    }
}
