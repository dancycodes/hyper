<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Sup Element
 *
 * Represents superscript text (text above the baseline).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-sub-and-sup-elements
 */
class Sup extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('sup');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
