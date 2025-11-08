<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Data Element
 *
 * Represents content with a machine-readable translation via the value attribute.
 * Useful for data that needs both human-readable and machine-readable formats.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-data-element
 */
class Data extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('data');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the value attribute (machine-readable value)
     *
     * @param string|Closure $value Value or closure
     */
    public function value(string|Closure $value): static
    {
        return $this->attr('value', $value);
    }
}
