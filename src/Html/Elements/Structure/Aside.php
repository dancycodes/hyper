<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Aside Element
 *
 * Represents content tangentially related to the main content
 * (e.g., sidebars, pull quotes, advertisements).
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-aside-element
 */
class Aside extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('aside');
    }
}
