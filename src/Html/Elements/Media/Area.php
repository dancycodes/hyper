<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Link\HasLinkAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

/**
 * Area Element
 *
 * Represents a hyperlink area within an image map.
 *
 * @see https://html.spec.whatwg.org/multipage/image-maps.html#the-area-element
 */
class Area extends VoidElement
{
    use HasLinkAttributes;

    public function __construct()
    {
        parent::__construct('area');
    }

    /**
     * Set the shape attribute (rect, circle, poly, default)
     *
     * @param string|Closure $shape Shape type or closure
     */
    public function shape(string|Closure $shape): static
    {
        return $this->attr('shape', $shape);
    }

    /**
     * Set the coords attribute (shape coordinates)
     *
     * @param string|Closure $coords Coordinates or closure
     */
    public function coords(string|Closure $coords): static
    {
        return $this->attr('coords', $coords);
    }

    /**
     * Set the alt attribute (alternative text)
     *
     * @param string|Closure $alt Alt text or closure
     */
    public function alt(string|Closure $alt): static
    {
        return $this->attr('alt', $alt);
    }
}
