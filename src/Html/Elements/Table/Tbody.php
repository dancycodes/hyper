<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Tbody Element
 *
 * Represents the body section of a table containing data rows.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tbody-element
 */
class Tbody extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('tbody');
    }
}
