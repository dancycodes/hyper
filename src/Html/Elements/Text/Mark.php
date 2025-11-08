<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Mark Element
 *
 * Represents text marked or highlighted for reference or notation purposes.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-mark-element
 */
class Mark extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('mark');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
