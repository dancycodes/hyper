<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Section Element
 *
 * Represents a generic section of content, typically with a heading.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-section-element
 */
class Section extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('section');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
