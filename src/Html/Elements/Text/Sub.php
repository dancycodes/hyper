<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Sub Element
 *
 * Represents subscript text (text below the baseline).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-sub-and-sup-elements
 */
class Sub extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('sub');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
