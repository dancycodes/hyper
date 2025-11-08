<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * Option Element
 *
 * Represents an option in a select dropdown or datalist.
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-option-element
 */
class Option extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('option');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * Set the value attribute
     *
     * @param string|int|float|Closure $value Option value or closure
     */
    public function value(string|int|float|Closure $value): static
    {
        return $this->attr('value', $value);
    }

    /**
     * Set the selected attribute
     *
     * @param bool|Closure $selected Selected state or closure
     */
    public function selected(bool|Closure $selected = true): static
    {
        return $this->attr('selected', $selected);
    }

    /**
     * Set the disabled attribute
     *
     * @param bool|Closure $disabled Disabled state or closure
     */
    public function disabled(bool|Closure $disabled = true): static
    {
        return $this->attr('disabled', $disabled);
    }

    /**
     * Set the label attribute (alternative display text)
     *
     * @param string|Closure $label Label text or closure
     */
    public function label(string|Closure $label): static
    {
        return $this->attr('label', $label);
    }
}
