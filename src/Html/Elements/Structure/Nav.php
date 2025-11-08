<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Nav Element
 *
 * Represents a section of navigation links.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-nav-element
 */
class Nav extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('nav');
    }
}
