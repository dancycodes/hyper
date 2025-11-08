<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Form;

use Closure;

/**
 * Input-specific attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasInputAttributes
{
    /**
     * Set the type attribute
     *
     * @param string|Closure $type Input type or closure returning type
     */
    public function type(string|Closure $type): static
    {
        return $this->attr('type', $type);
    }

    /**
     * Set the placeholder attribute
     *
     * @param string|Closure $text Placeholder text or closure returning text
     */
    public function placeholder(string|Closure $text): static
    {
        return $this->attr('placeholder', $text);
    }

    /**
     * Set the required attribute
     *
     * Specifies that the input field must be filled out before submitting the form.
     *
     * @param bool|Closure $required Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-required
     */
    public function required(bool|Closure $required = true): static
    {
        return $this->attr('required', $required);
    }

    /**
     * Set the disabled attribute
     *
     * Specifies that the input field should be disabled and not editable.
     * Disabled fields are not submitted with the form.
     *
     * @param bool|Closure $disabled Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#attr-fe-disabled
     */
    public function disabled(bool|Closure $disabled = true): static
    {
        return $this->attr('disabled', $disabled);
    }

    /**
     * Set the readonly attribute
     *
     * Specifies that the input field is read-only and cannot be modified by the user.
     * Unlike disabled, readonly fields are still submitted with the form.
     *
     * @param bool|Closure $readonly Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-readonly
     */
    public function readonly(bool|Closure $readonly = true): static
    {
        return $this->attr('readonly', $readonly);
    }

    /**
     * Set the checked attribute (checkbox/radio)
     *
     * Specifies that a checkbox or radio button should be pre-selected.
     *
     * @param bool|Closure $checked Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-checked
     */
    public function checked(bool|Closure $checked = true): static
    {
        return $this->attr('checked', $checked);
    }

    /**
     * Set the maxlength attribute
     *
     * Specifies the maximum number of characters allowed in a text input.
     *
     * @param int|Closure $length Maximum length or closure returning length
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-maxlength
     */
    public function maxlength(int|Closure $length): static
    {
        return $this->attr('maxlength', $length);
    }

    /**
     * Set the minlength attribute
     *
     * Specifies the minimum number of characters required in a text input.
     *
     * @param int|Closure $length Minimum length or closure returning length
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-minlength
     */
    public function minlength(int|Closure $length): static
    {
        return $this->attr('minlength', $length);
    }

    /**
     * Set the max attribute (number/date inputs)
     *
     * Specifies the maximum value for number, date, time, and range inputs.
     * Can accept numeric values or date strings (e.g., "2024-12-31").
     *
     * @param int|string|Closure $value Maximum value or closure returning value
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-max
     */
    public function max(int|string|Closure $value): static
    {
        return $this->attr('max', $value);
    }

    /**
     * Set the min attribute (number/date inputs)
     *
     * Specifies the minimum value for number, date, time, and range inputs.
     * Can accept numeric values or date strings (e.g., "2024-01-01").
     *
     * @param int|string|Closure $value Minimum value or closure returning value
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-min
     */
    public function min(int|string|Closure $value): static
    {
        return $this->attr('min', $value);
    }

    /**
     * Set the pattern attribute (regex validation)
     *
     * Specifies a regular expression that the input's value must match for validation.
     * The pattern is matched against the entire value, not a subset.
     *
     * @param string|Closure $pattern Regex pattern or closure returning pattern
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-pattern
     */
    public function pattern(string|Closure $pattern): static
    {
        return $this->attr('pattern', $pattern);
    }

    /**
     * Set the step attribute (number inputs)
     *
     * Specifies the legal number intervals for number, range, date, and time inputs.
     * Can accept numeric values or the string "any" to allow any value.
     *
     * @param int|string|Closure $value Step value or closure returning value (number or "any")
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-step
     */
    public function step(int|string|Closure $value): static
    {
        return $this->attr('step', $value);
    }

    /**
     * Set the accept attribute (file inputs)
     *
     * Specifies the types of files that the server accepts (for file upload).
     * Can specify MIME types (e.g., "image/png") or file extensions (e.g., ".pdf").
     * Multiple values should be comma-separated.
     *
     * @param string|Closure $types Accepted file types or closure returning types
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-accept
     */
    public function accept(string|Closure $types): static
    {
        return $this->attr('accept', $types);
    }

    /**
     * Set the multiple attribute
     *
     * Specifies that the user can enter/select multiple values.
     * Used with email inputs (comma-separated emails) and file inputs (multiple files).
     *
     * @param bool|Closure $multiple Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/input.html#attr-input-multiple
     */
    public function multiple(bool|Closure $multiple = true): static
    {
        return $this->attr('multiple', $multiple);
    }
}
