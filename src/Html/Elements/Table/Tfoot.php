<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Tfoot Element
 *
 * Represents the footer section of a table containing summary rows.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tfoot-element
 */
class Tfoot extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('tfoot');
    }
}
