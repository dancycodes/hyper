<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Table\HasTableAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Colgroup Element
 *
 * Represents a group of columns in a table.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-colgroup-element
 */
class Colgroup extends ContainerElement
{
    use HasTableAttributes;

    public function __construct()
    {
        parent::__construct('colgroup');
    }

    /**
     * Set the span attribute (number of columns in the group)
     *
     * @param int|Closure $columns Number of columns or closure
     */
    public function span(int|Closure $columns): static
    {
        return $this->attr('span', $columns);
    }
}
