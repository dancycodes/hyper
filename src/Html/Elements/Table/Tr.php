<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Tr Element
 *
 * Represents a row of cells in a table.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tr-element
 */
class Tr extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('tr');
    }
}
