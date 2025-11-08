<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * I Element
 *
 * Represents text in an alternate voice or mood, such as technical terms,
 * foreign language phrases, thoughts, or taxonomic designations.
 *
 * Note: Use <em> for emphasis, <i> for alternate voice/mood.
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-i-element
 */
class I extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('i');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
