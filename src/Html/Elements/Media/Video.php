<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Video Element
 *
 * Represents a video player for video content.
 *
 * @see https://html.spec.whatwg.org/multipage/media.html#the-video-element
 */
class Video extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('video');
    }

    /**
     * Set the playsinline attribute (play inline on mobile)
     *
     * Specifies that the video should play inline on mobile devices rather than
     * entering fullscreen mode.
     *
     * @param bool $playsinline Boolean value
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-video-playsinline
     */
    public function playsinline(bool $playsinline = true): static
    {
        return $this->attr('playsinline', $playsinline);
    }
}
