<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Datalist Element
 *
 * Represents a list of predefined options for an input element.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-datalist-element
 */
class Datalist extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('datalist');
    }
}
