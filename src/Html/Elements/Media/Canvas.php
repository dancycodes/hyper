<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Canvas Element
 *
 * Represents a bitmap canvas for drawing graphics via JavaScript.
 *
 * @see https://html.spec.whatwg.org/multipage/canvas.html#the-canvas-element
 */
class Canvas extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('canvas');
    }
}
