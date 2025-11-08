<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Dfn Element
 *
 * Represents the defining instance of a term.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-dfn-element
 */
class Dfn extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('dfn');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
