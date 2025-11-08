<?php

namespace Dancycodes\Hyper\Html\Elements\Interactive;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Summary Element
 *
 * Represents a summary, caption, or legend for a details element.
 *
 * @see https://html.spec.whatwg.org/multipage/interactive-elements.html#the-summary-element
 */
class Summary extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('summary');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
