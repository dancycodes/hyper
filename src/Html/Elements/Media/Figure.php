<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Figure Element
 *
 * Represents self-contained content (images, diagrams, code) with optional caption.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-figure-element
 */
class Figure extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('figure');
    }
}
