<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Form;

use Closure;

/**
 * Form-related attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasFormAttributes
{
    /**
     * Set the name attribute
     *
     * Specifies the name of the form control, which is submitted with the form data.
     *
     * @param string|Closure $name Field name or closure returning name
     *
     * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#attr-fe-name
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }

    /**
     * Set the value attribute
     *
     * Specifies the default value of the form control. The type of value depends on
     * the control type (text, number, date, etc.).
     *
     * @param string|int|float|Closure $value Field value or closure returning value
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-value
     */
    public function value(string|int|float|Closure $value): static
    {
        return $this->attr('value', $value);
    }

    /**
     * Set the form attribute (associates with form by ID)
     *
     * Associates the control with a form element (by ID). This allows the control
     * to be located anywhere in the document, not just inside the form element.
     *
     * @param string|Closure $formId Form ID or closure returning form ID
     *
     * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#attr-fae-form
     */
    public function form(string|Closure $formId): static
    {
        return $this->attr('form', $formId);
    }

    /**
     * Set the autocomplete attribute
     *
     * Specifies whether the browser should provide automated assistance in filling
     * out form field values, and guidance to the browser about the type of information
     * expected in the field.
     *
     * Common values: "on", "off", "name", "email", "username", "new-password",
     * "current-password", "tel", "street-address", "postal-code", etc.
     *
     * @param string|Closure $value Autocomplete value or closure returning value
     *
     * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#attr-fe-autocomplete
     */
    public function autocomplete(string|Closure $value): static
    {
        return $this->attr('autocomplete', $value);
    }

    /**
     * Set the autofocus attribute
     *
     * Specifies that the form control should automatically get focus when the page loads.
     * Only one element in a document should have the autofocus attribute.
     *
     * @param bool|Closure $autofocus Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#attr-fe-autofocus
     */
    public function autofocus(bool|Closure $autofocus = true): static
    {
        return $this->attr('autofocus', $autofocus);
    }
}
