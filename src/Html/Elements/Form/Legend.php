<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Legend Element
 *
 * Represents a caption/title for a fieldset.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-legend-element
 */
class Legend extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('legend');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
