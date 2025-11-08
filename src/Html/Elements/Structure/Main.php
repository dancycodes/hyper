<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Main Element
 *
 * Represents the dominant content of the document body.
 * There should only be one <main> element per document.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-main-element
 */
class Main extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('main');
    }
}
