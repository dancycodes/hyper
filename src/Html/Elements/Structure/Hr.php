<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Hr Element
 *
 * Represents a thematic break (horizontal rule) between content.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-hr-element
 */
class Hr extends VoidElement
{
    public function __construct()
    {
        parent::__construct('hr');
    }
}
