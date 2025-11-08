<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * Samp Element
 *
 * Represents sample or quoted output from a computer program.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-samp-element
 */
class Samp extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('samp');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
