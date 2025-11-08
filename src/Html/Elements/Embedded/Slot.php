<?php

namespace Dancycodes\Hyper\Html\Elements\Embedded;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Slot Element
 *
 * Represents a placeholder for web component content.
 *
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-slot-element
 */
class Slot extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('slot');
    }

    /**
     * Set the name attribute (slot name)
     *
     * @param string|Closure $name Slot name or closure
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }
}
