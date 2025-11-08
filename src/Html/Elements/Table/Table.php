<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Table Element
 *
 * Represents tabular data organized in rows and columns.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-table-element
 */
class Table extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('table');
    }
}
