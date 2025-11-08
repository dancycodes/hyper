<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Ins Element
 *
 * Represents an addition or insertion to the document.
 *
 * @see https://html.spec.whatwg.org/multipage/edits.html#the-ins-element
 */
class Ins extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('ins');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the cite attribute (URL explaining the change)
     *
     * @param string|Closure $url Source URL or closure
     */
    public function cite(string|Closure $url): static
    {
        return $this->attr('cite', $url);
    }

    /**
     * Set the datetime attribute (when the change was made)
     *
     * @param string|Closure $datetime Datetime value or closure
     */
    public function datetime(string|Closure $datetime): static
    {
        return $this->attr('datetime', $datetime);
    }
}
