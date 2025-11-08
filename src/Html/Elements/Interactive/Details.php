<?php

namespace Dancycodes\Hyper\Html\Elements\Interactive;

use Dancycodes\Hyper\Html\Concerns\Attributes\Interactive\HasInteractiveAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Details Element
 *
 * Represents a disclosure widget with expandable/collapsible content.
 *
 * @see https://html.spec.whatwg.org/multipage/interactive-elements.html#the-details-element
 */
class Details extends ContainerElement
{
    use HasInteractiveAttributes;

    public function __construct()
    {
        parent::__construct('details');
    }
}
