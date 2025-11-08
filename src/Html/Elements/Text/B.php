<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * B Element
 *
 * Represents text that draws attention without conveying extra importance.
 * Used for keywords, product names, or other text that should stand out visually.
 *
 * Note: Use <strong> for important text, <b> for stylistic text.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-b-element
 */
class B extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('b');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
