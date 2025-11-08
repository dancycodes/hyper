<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Blockquote Element
 *
 * Represents a section that is quoted from another source.
 *
 * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-blockquote-element
 */
class Blockquote extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('blockquote');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the cite attribute (URL of the quote source)
     *
     * @param string|Closure $url Source URL or closure
     */
    public function cite(string|Closure $url): static
    {
        return $this->attr('cite', $url);
    }
}
