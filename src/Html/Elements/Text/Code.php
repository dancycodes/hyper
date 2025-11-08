<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * Code Element
 *
 * Represents a fragment of computer code.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-code-element
 */
class Code extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('code');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
