<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Address Element
 *
 * Represents contact information for a person, people, or organization.
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-address-element
 */
class Address extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('address');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
