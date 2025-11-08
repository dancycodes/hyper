<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Del Element
 *
 * Represents a removal or deletion from the document.
 *
 * @see https://html.spec.whatwg.org/multipage/edits.html#the-del-element
 */
class Del extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('del');
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
