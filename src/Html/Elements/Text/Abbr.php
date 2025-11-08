<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Abbr Element
 *
 * Represents an abbreviation or acronym.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-abbr-element
 */
class Abbr extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('abbr');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
