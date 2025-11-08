<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Select Element
 *
 * Represents a dropdown list control.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-select-element
 */
class Select extends ContainerElement
{
    use HasFormAttributes;

    public function __construct()
    {
        parent::__construct('select');
    }

    /**
     * Set the multiple attribute (allow multiple selections)
     *
     * @param bool|Closure $multiple Allow multiple selections or closure
     */
    public function multiple(bool|Closure $multiple = true): static
    {
        return $this->attr('multiple', $multiple);
    }

    /**
     * Set the size attribute (number of visible options)
     *
     * @param int|Closure $size Number of visible options or closure
     */
    public function size(int|Closure $size): static
    {
        return $this->attr('size', $size);
    }

    /**
     * Set the required attribute
     *
     * @param bool|Closure $required Required field or closure
     */
    public function required(bool|Closure $required = true): static
    {
        return $this->attr('required', $required);
    }
}
