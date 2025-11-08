<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * S Element
 *
 * Represents content that is no longer accurate or no longer relevant.
 * Typically rendered with a strikethrough.
 *
 * Note: Use <del> to indicate document edits, <s> for irrelevant content.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-s-element
 */
class S extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('s');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
