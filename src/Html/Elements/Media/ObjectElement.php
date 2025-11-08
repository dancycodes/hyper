<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Object Element
 *
 * Represents an external resource (image, plugin, nested browsing context).
 *
 * Note: Named ObjectElement to avoid PHP reserved keyword 'object'
 *
 * @see https://html.spec.whatwg.org/multipage/iframe-embed-object.html#the-object-element
 */
class ObjectElement extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('object');
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
     * Set the data attribute (resource URL)
     *
     * @param string|Closure $url Resource URL or closure
     */
    public function data(string|Closure $url): static
    {
        return $this->attr('data', $url);
    }

    /**
     * Set the name attribute (browsing context name)
     *
     * @param string|Closure $name Context name or closure
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }

    /**
     * Set the form attribute (associated form ID)
     *
     * @param string|Closure $formId Form ID or closure
     */
    public function form(string|Closure $formId): static
    {
        return $this->attr('form', $formId);
    }
}
