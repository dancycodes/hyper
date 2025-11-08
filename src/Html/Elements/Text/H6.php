<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * H6 Element
 *
 * Represents a level 6 heading.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-h1,-h2,-h3,-h4,-h5,-and-h6-elements
 */
class H6 extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('h6');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
