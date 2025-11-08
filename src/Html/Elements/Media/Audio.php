<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Audio Element
 *
 * Represents an audio player for sound content.
 *
 * @see https://html.spec.whatwg.org/multipage/media.html#the-audio-element
 */
class Audio extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('audio');
    }

    // All media methods (src, controls, autoplay, loop, muted, preload, etc.)
    // are provided by the HasMediaAttributes trait
}
