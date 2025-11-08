<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * U Element
 *
 * Represents text with an unarticulated, non-textual annotation.
 * Used for: misspellings, proper names in Chinese, etc.
 *
 * Note: Not for general underlining (use CSS). Has semantic meaning.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-u-element
 */
class U extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('u');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
