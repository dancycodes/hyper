<?php

namespace Dancycodes\Hyper\Html\Elements\List;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Dl Element
 *
 * Represents a description list (term/definition pairs).
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-dl-element
 */
class Dl extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('dl');
    }
}
