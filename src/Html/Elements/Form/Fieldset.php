<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Fieldset Element
 *
 * Represents a group of form controls with a common purpose.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-fieldset-element
 */
class Fieldset extends ContainerElement
{
    use HasFormAttributes;

    public function __construct()
    {
        parent::__construct('fieldset');
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
