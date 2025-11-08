<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Track Element
 *
 * Represents text tracks for audio/video elements (subtitles, captions, etc.).
 *
 * @see https://html.spec.whatwg.org/multipage/media.html#the-track-element
 */
class Track extends VoidElement
{
    public function __construct()
    {
        parent::__construct('track');
    }

    /**
     * Set the src attribute (track file URL)
     *
     * @param string|Closure $url Track file URL or closure
     */
    public function src(string|Closure $url): static
    {
        return $this->attr('src', $url);
    }

    /**
     * Set the kind attribute (subtitles, captions, descriptions, chapters, metadata)
     *
     * @param string|Closure $kind Track kind or closure
     */
    public function kind(string|Closure $kind): static
    {
        return $this->attr('kind', $kind);
    }

    /**
     * Set the srclang attribute (language code)
     *
     * @param string|Closure $lang Language code or closure
     */
    public function srclang(string|Closure $lang): static
    {
        return $this->attr('srclang', $lang);
    }

    /**
     * Set the label attribute (user-readable title)
     *
     * @param string|Closure $label Label text or closure
     */
    public function label(string|Closure $label): static
    {
        return $this->attr('label', $label);
    }

    /**
     * Set the default attribute (default track)
     *
     * @param bool|Closure $default Default state or closure
     */
    public function defaultTrack(bool|Closure $default = true): static
    {
        return $this->attr('default', $default);
    }
}
