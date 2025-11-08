<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Search Element
 *
 * Represents a section containing search or filtering functionality.
 * Semantic container for search interfaces.
 *
 * Added to HTML Living Standard in 2023.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-search-element
 */
class Search extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('search');
    }
}
