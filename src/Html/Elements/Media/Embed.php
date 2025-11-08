<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Embed Element
 *
 * Represents an integration point for external content (plugin).
 *
 * @see https://html.spec.whatwg.org/multipage/iframe-embed-object.html#the-embed-element
 */
class Embed extends VoidElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('embed');
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
}
