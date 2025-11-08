<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * H5 Element
 *
 * Represents a level 5 heading.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-h1,-h2,-h3,-h4,-h5,-and-h6-elements
 */
class H5 extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('h5');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
