<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Media;

use Closure;

/**
 * Media element attributes (img, audio, video, etc.)
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasMediaAttributes
{
    /**
     * Set the src attribute
     *
     * Specifies the URL of the media resource (image, audio, video, etc.).
     *
     * @param string|Closure $url Source URL or closure
     *
     * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-media-src
     */
    public function src(string|Closure $url): static
    {
        return $this->attr('src', $url);
    }

    /**
     * Set the alt attribute
     *
     * Provides alternative text for images when they cannot be displayed.
     * Essential for accessibility and SEO.
     *
     * @param string|Closure $text Alt text or closure
     *
     * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-alt
     */
    public function alt(string|Closure $text): static
    {
        return $this->attr('alt', $text);
    }

    /**
     * Set the width attribute
     *
     * Specifies the width of the media element in pixels. Can be an integer
     * or a string representation.
     *
     * @param int|string|Closure $width Width value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-width
     */
    public function width(int|string|Closure $width): static
    {
        return $this->attr('width', $width);
    }

    /**
     * Set the height attribute
     *
     * Specifies the height of the media element in pixels. Can be an integer
     * or a string representation.
     *
     * @param int|string|Closure $height Height value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     */
    public function height(int|string|Closure $height): static
    {
        return $this->attr('height', $height);
    }

    /**
     * Set the loading attribute (lazy, eager)
     *
     * Specifies the loading strategy for the media element:
     * - lazy: Defer loading until the element is near the viewport
     * - eager: Load immediately
     *
     * @param string|Closure $value Loading strategy or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes
     */
    public function loading(string|Closure $value): static
    {
        $value = $this->evaluate($value);

        if (!in_array($value, ['lazy', 'eager'], true)) {
            throw new \InvalidArgumentException(
                'Invalid value for loading() attribute: ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }

        return $this->attr('loading', $value);
    }

    /**
     * Set the controls attribute (audio/video)
     *
     * Specifies whether to display playback controls (play, pause, volume, etc.)
     * for audio and video elements.
     *
     * @param bool|Closure $controls Boolean or closure
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-media-controls
     */
    public function controls(bool|Closure $controls = true): static
    {
        return $this->attr('controls', $controls);
    }

    /**
     * Set the autoplay attribute
     *
     * Specifies whether the media should start playing automatically when the page loads.
     * Note: Browsers may restrict autoplay, especially for videos with audio.
     *
     * @param bool|Closure $autoplay Boolean or closure
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-media-autoplay
     */
    public function autoplay(bool|Closure $autoplay = true): static
    {
        return $this->attr('autoplay', $autoplay);
    }

    /**
     * Set the loop attribute
     *
     * Specifies whether the media should restart from the beginning when it reaches the end.
     *
     * @param bool|Closure $loop Boolean or closure
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-media-loop
     */
    public function loop(bool|Closure $loop = true): static
    {
        return $this->attr('loop', $loop);
    }

    /**
     * Set the muted attribute
     *
     * Specifies whether the audio output should be muted by default.
     * Useful for autoplaying videos to comply with browser autoplay policies.
     *
     * @param bool|Closure $muted Boolean or closure
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-media-muted
     */
    public function muted(bool|Closure $muted = true): static
    {
        return $this->attr('muted', $muted);
    }

    /**
     * Set the poster attribute (video thumbnail)
     *
     * Specifies an image URL to show while the video is downloading or until the user
     * hits the play button. If not specified, the first frame is used.
     *
     * @param string|Closure $url Poster URL or closure
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-video-poster
     */
    public function poster(string|Closure $url): static
    {
        return $this->attr('poster', $url);
    }

    /**
     * Set the preload attribute (none, metadata, auto)
     *
     * Specifies how the browser should preload the media:
     * - none: Do not preload
     * - metadata: Preload only metadata (duration, dimensions, etc.)
     * - auto: Let the browser decide (usually preloads the entire file)
     *
     * @param string|Closure $value Preload strategy or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://html.spec.whatwg.org/multipage/media.html#attr-media-preload
     */
    public function preload(string|Closure $value): static
    {
        $value = $this->evaluate($value);

        if (!in_array($value, ['none', 'metadata', 'auto'], true)) {
            throw new \InvalidArgumentException(
                'Invalid value for preload() attribute: ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }

        return $this->attr('preload', $value);
    }
}
