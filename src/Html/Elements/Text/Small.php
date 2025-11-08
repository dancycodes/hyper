<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Small Element
 *
 * Represents side comments and small print (fine print, legal text).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-small-element
 */
class Small extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('small');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
