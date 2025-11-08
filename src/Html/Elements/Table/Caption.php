<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Caption Element
 *
 * Represents the title/caption of a table.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-caption-element
 */
class Caption extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('caption');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
