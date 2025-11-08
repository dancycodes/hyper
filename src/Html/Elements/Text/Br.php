<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Br Element
 *
 * Represents a line break.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-br-element
 */
class Br extends VoidElement
{
    public function __construct()
    {
        parent::__construct('br');
    }
}
