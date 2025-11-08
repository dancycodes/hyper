<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Figcaption Element
 *
 * Represents a caption or legend for a figure element.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-figcaption-element
 */
class Figcaption extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('figcaption');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
