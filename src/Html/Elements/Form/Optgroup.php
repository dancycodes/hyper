<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Optgroup Element
 *
 * Represents a group of options in a select dropdown.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-optgroup-element
 */
class Optgroup extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('optgroup');
    }

    /**
     * Set the label attribute (group label)
     *
     * @param string|Closure $label Group label or closure
     */
    public function label(string|Closure $label): static
    {
        return $this->attr('label', $label);
    }

    /**
     * Set the disabled attribute
     *
     * @param bool|Closure $disabled Disabled state or closure
     */
    public function disabled(bool|Closure $disabled = true): static
    {
        return $this->attr('disabled', $disabled);
    }
}
