<?php

namespace Dancycodes\Hyper\Html\Elements\Embedded;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Wbr Element
 *
 * Represents a word break opportunity (where the browser may optionally break a line).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-wbr-element
 */
class Wbr extends VoidElement
{
    public function __construct()
    {
        parent::__construct('wbr');
    }
}
