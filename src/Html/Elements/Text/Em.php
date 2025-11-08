<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Em Element
 *
 * Represents stress emphasis of its contents.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-em-element
 */
class Em extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('em');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
