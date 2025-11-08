<?php

namespace Dancycodes\Hyper\Html\Elements\List;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Dt Element
 *
 * Represents a term in a description list.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-dt-element
 */
class Dt extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('dt');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
