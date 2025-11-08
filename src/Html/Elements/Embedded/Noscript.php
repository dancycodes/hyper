<?php

namespace Dancycodes\Hyper\Html\Elements\Embedded;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Noscript Element
 *
 * Represents content to display when JavaScript is disabled.
 *
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-noscript-element
 */
class Noscript extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('noscript');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
