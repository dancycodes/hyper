<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Bdi Element
 *
 * Represents text that should be isolated from its surroundings for
 * bidirectional text formatting. Useful when embedding user-generated
 * content with unknown text direction.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-bdi-element
 */
class Bdi extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('bdi');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
