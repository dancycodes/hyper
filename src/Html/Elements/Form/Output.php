<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Output Element
 *
 * Represents the result of a calculation or user action.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-output-element
 */
class Output extends ContainerElement
{
    use HasFormAttributes;

    public function __construct(?string $text = null)
    {
        parent::__construct('output');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the for attribute (IDs of elements that contributed to the output)
     *
     * @param string|Closure $ids Space-separated element IDs or closure
     */
    public function forElements(string|Closure $ids): static
    {
        return $this->attr('for', $ids);
    }
}
