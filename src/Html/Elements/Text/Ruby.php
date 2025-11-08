<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Ruby Element
 *
 * Represents a ruby annotation container, used for showing pronunciation
 * or translation of East Asian characters. Contains text and <rt> elements.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-ruby-element
 */
class Ruby extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('ruby');
    }
}
