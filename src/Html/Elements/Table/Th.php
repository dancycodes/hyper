<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Concerns\Attributes\Table\HasTableAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Th Element
 *
 * Represents a header cell in a table.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-th-element
 */
class Th extends ContainerElement
{
    use HasTableAttributes;

    public function __construct(?string $text = null)
    {
        parent::__construct('th');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
