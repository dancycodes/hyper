<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Cite Element
 *
 * Represents the title of a creative work (book, article, song, etc.).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-cite-element
 */
class Cite extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('cite');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
