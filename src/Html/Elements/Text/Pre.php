<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Pre Element
 *
 * Represents preformatted text where whitespace and line breaks are preserved.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-pre-element
 */
class Pre extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('pre');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
