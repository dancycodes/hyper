<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Time Element
 *
 * Represents a specific time or date.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-time-element
 */
class Time extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('time');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the datetime attribute (machine-readable date/time)
     *
     * @param string|Closure $datetime Datetime value or closure
     */
    public function datetime(string|Closure $datetime): static
    {
        return $this->attr('datetime', $datetime);
    }
}
