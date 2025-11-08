<?php

namespace Dancycodes\Hyper\Html\Elements\Table;

use Dancycodes\Hyper\Html\Concerns\Attributes\Table\HasTableAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Td Element
 *
 * Represents a data cell in a table.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-td-element
 */
class Td extends ContainerElement
{
    use HasTableAttributes;

    public function __construct(?string $text = null)
    {
        parent::__construct('td');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
