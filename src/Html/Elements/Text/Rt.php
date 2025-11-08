<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Rt Element
 *
 * Represents the ruby text component of a ruby annotation.
 * Contains the pronunciation or translation shown above/beside the base text.
 *
 * Must be used inside a <ruby> element.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-rt-element
 */
class Rt extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('rt');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
