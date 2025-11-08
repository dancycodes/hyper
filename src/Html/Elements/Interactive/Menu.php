<?php

namespace Dancycodes\Hyper\Html\Elements\Interactive;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Menu Element
 *
 * Represents a list of commands or options.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-menu-element
 */
class Menu extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('menu');
    }
}
