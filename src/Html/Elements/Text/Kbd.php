<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * Kbd Element
 *
 * Represents user input from a keyboard, voice input, or other text entry device.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-kbd-element
 */
class Kbd extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('kbd');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
