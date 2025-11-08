<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Table\HasTableAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Col Element
 *
 * Represents a column in a table (used within colgroup).
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-col-element
 */
class Col extends VoidElement
{
    use HasTableAttributes;

    public function __construct()
    {
        parent::__construct('col');
    }

    /**
     * Set the span attribute (number of columns)
     *
     * @param int|Closure $columns Number of columns or closure
     */
    public function span(int|Closure $columns): static
    {
        return $this->attr('span', $columns);
    }
}
