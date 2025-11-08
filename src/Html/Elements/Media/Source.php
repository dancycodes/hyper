<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Source Element
 *
 * Represents a media resource for audio, video, or picture elements.
 *
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-source-element
 */
class Source extends VoidElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('source');
    }

    /**
     * Set the type attribute (MIME type)
     *
     * @param string|Closure $mimeType MIME type or closure
     */
    public function type(string|Closure $mimeType): static
    {
        return $this->attr('type', $mimeType);
    }

    /**
     * Set the media attribute (media query)
     *
     * @param string|Closure $query Media query or closure
     */
    public function media(string|Closure $query): static
    {
        return $this->attr('media', $query);
    }

    /**
     * Set the srcset attribute (responsive images)
     *
     * @param string|Closure $srcset Srcset value or closure
     */
    public function srcset(string|Closure $srcset): static
    {
        return $this->attr('srcset', $srcset);
    }

    /**
     * Set the sizes attribute (responsive image sizes)
     *
     * @param string|Closure $sizes Sizes value or closure
     */
    public function sizes(string|Closure $sizes): static
    {
        return $this->attr('sizes', $sizes);
    }
}
