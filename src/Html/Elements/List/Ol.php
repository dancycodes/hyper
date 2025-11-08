<?php

namespace Dancycodes\Hyper\Html\Elements\List;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Ol Element
 *
 * Represents an ordered list of items.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-ol-element
 */
class Ol extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('ol');
    }

    /**
     * Set the start attribute (starting value of the list)
     *
     * @param int|Closure $value Starting value or closure
     */
    public function start(int|Closure $value): static
    {
        return $this->attr('start', $value);
    }

    /**
     * Set the type attribute (numbering type: 1, a, A, i, I)
     *
     * @param string|Closure $type Numbering type or closure
     */
    public function type(string|Closure $type): static
    {
        return $this->attr('type', $type);
    }

    /**
     * Set the reversed attribute (reverse the list order)
     *
     * @param bool|Closure $reversed Reverse order or closure
     */
    public function reversed(bool|Closure $reversed = true): static
    {
        return $this->attr('reversed', $reversed);
    }
}
