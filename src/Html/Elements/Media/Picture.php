<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Picture Element
 *
 * Represents a container for multiple image sources for responsive images.
 *
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-picture-element
 */
class Picture extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('picture');
    }
}
