<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Bdo Element
 *
 * Represents explicit text directionality override.
 * Requires the dir attribute (ltr or rtl).
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-bdo-element
 */
class Bdo extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('bdo');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the dir attribute (required: ltr or rtl)
     *
     * @param string|Closure $direction Direction value or closure
     */
    public function dir(string|Closure $direction): static
    {
        return $this->attr('dir', $direction);
    }
}
