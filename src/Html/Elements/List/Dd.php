<?php

namespace Dancycodes\Hyper\Html\Elements\List;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Dd Element
 *
 * Represents a description/definition in a description list.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-dd-element
 */
class Dd extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('dd');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
