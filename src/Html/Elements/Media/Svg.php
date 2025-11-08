<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Svg Element
 *
 * Represents the root element for SVG (Scalable Vector Graphics) content.
 *
 * @see https://html.spec.whatwg.org/multipage/embedded-content-other.html#svg-0
 */
class Svg extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('svg');
    }

    /**
     * Set the viewBox attribute (viewport coordinates)
     *
     * @param string|Closure $viewBox ViewBox value or closure
     */
    public function viewBox(string|Closure $viewBox): static
    {
        return $this->attr('viewBox', $viewBox);
    }

    /**
     * Set the xmlns attribute (XML namespace)
     *
     * @param string|Closure $namespace XML namespace or closure
     */
    public function xmlns(string|Closure $namespace = 'http://www.w3.org/2000/svg'): static
    {
        return $this->attr('xmlns', $namespace);
    }
}
