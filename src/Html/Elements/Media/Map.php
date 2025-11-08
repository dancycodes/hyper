<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Map Element
 *
 * Represents an image map with clickable areas.
 *
 * @see https://html.spec.whatwg.org/multipage/image-maps.html#the-map-element
 */
class Map extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('map');
    }

    /**
     * Set the name attribute (map name for usemap reference)
     *
     * @param string|Closure $name Map name or closure
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }
}
