<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Rp Element
 *
 * Represents parentheses around ruby text for browsers that don't support
 * ruby annotations. Provides fallback presentation.
 *
 * Must be used inside a <ruby> element.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-rp-element
 */
class Rp extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('rp');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
