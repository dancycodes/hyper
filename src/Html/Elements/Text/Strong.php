<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Strong Element
 *
 * Represents strong importance, seriousness, or urgency for its contents.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-strong-element
 */
class Strong extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('strong');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
