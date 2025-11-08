<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Param Element
 *
 * Represents a parameter for an object element.
 *
 * @see https://html.spec.whatwg.org/multipage/iframe-embed-object.html#the-param-element
 */
class Param extends VoidElement
{
    public function __construct()
    {
        parent::__construct('param');
    }

    /**
     * Set the name attribute (parameter name)
     *
     * @param string|Closure $name Parameter name or closure
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }

    /**
     * Set the value attribute (parameter value)
     *
     * @param string|Closure $value Parameter value or closure
     */
    public function value(string|Closure $value): static
    {
        return $this->attr('value', $value);
    }
}
