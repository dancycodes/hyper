<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Thead Element
 *
 * Represents the header section of a table containing column headers.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-thead-element
 */
class Thead extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('thead');
    }
}
