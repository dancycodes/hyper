<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Footer Element
 *
 * Represents a footer section typically containing copyright,
 * author information, or related links.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-footer-element
 */
class Footer extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('footer');
    }
}
