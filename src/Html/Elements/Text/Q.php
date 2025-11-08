<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Q Element
 *
 * Represents a short inline quotation. Browsers typically render this
 * with quotation marks around the content.
 *
 * Note: Use <blockquote> for longer quotations.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-q-element
 */
class Q extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('q');
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
