<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Core;

use Closure;

/**
 * ARIA (Accessible Rich Internet Applications) attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 *
 * @see https://www.w3.org/WAI/ARIA/apg/
 */
trait HasAriaAttributes
{
    /**
     * Set the role attribute
     *
     * Defines the element's role in the document structure for assistive technologies.
     * Common values: "button", "navigation", "main", "complementary", "banner", "contentinfo"
     *
     * @param string|Closure $role ARIA role or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#role_definitions
     */
    public function role(string|Closure $role): static
    {
        return $this->attr('role', $role);
    }

    /**
     * Set the aria-label attribute
     *
     * Provides an accessible label for the element when visible text label isn't available.
     *
     * @param string|Closure $label Label text or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-label
     */
    public function ariaLabel(string|Closure $label): static
    {
        return $this->attr('aria-label', $label);
    }

    /**
     * Set the aria-labelledby attribute
     *
     * References element IDs that provide a label for this element.
     *
     * @param string|Closure $ids Space-separated IDs or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-labelledby
     */
    public function ariaLabelledby(string|Closure $ids): static
    {
        return $this->attr('aria-labelledby', $ids);
    }

    /**
     * Set the aria-describedby attribute
     *
     * References element IDs that provide a description for this element.
     *
     * @param string|Closure $ids Space-separated IDs or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-describedby
     */
    public function ariaDescribedby(string|Closure $ids): static
    {
        return $this->attr('aria-describedby', $ids);
    }

    /**
     * Set the aria-hidden attribute
     *
     * Indicates whether the element is hidden from assistive technologies.
     *
     * @param bool|Closure $hidden Boolean or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-hidden
     */
    public function ariaHidden(bool|Closure $hidden = true): static
    {
        $hidden = $this->evaluate($hidden);

        return $this->attr('aria-hidden', $hidden ? 'true' : 'false');
    }

    /**
     * Set the aria-live attribute
     *
     * Indicates that an element will be updated, and describes the priority of updates:
     * - off: No announcements
     * - polite: Announce when user is idle
     * - assertive: Announce immediately, interrupting current speech
     *
     * @param string|Closure $value Live region value (off, polite, assertive) or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-live
     */
    public function ariaLive(string|Closure $value): static
    {
        $value = $this->evaluate($value);

        if (!in_array($value, ['off', 'polite', 'assertive'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid value for aria-live attribute on <%s> element. ' .
                    "Expected 'off', 'polite', or 'assertive', got: '%s'. " .
                    'This attribute indicates regions that will be updated dynamically. ' .
                    'See: https://www.w3.org/WAI/ARIA/apg/',
                    $this->tag ?? 'unknown',
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        return $this->attr('aria-live', $value);
    }

    /**
     * Set the aria-expanded attribute
     *
     * Indicates whether a collapsible element is expanded or collapsed.
     *
     * @param bool|Closure $expanded Boolean or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-expanded
     */
    public function ariaExpanded(bool|Closure $expanded = true): static
    {
        $expanded = $this->evaluate($expanded);

        return $this->attr('aria-expanded', $expanded ? 'true' : 'false');
    }

    /**
     * Set the aria-selected attribute
     *
     * Indicates the selection state of selectable elements (tabs, options, etc.).
     *
     * @param bool|Closure $selected Boolean or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-selected
     */
    public function ariaSelected(bool|Closure $selected = true): static
    {
        $selected = $this->evaluate($selected);

        return $this->attr('aria-selected', $selected ? 'true' : 'false');
    }

    /**
     * Set the aria-checked attribute
     *
     * Indicates the checked state of checkboxes, radio buttons, and similar elements.
     * Can be true, false, or 'mixed' for indeterminate state.
     *
     * @param bool|string|Closure $checked Boolean, 'mixed', or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-checked
     */
    public function ariaChecked(bool|string|Closure $checked = true): static
    {
        $checked = $this->evaluate($checked);

        if (is_bool($checked)) {
            $checked = $checked ? 'true' : 'false';
        } elseif (!in_array($checked, ['true', 'false', 'mixed'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid value for aria-checked attribute on <%s> element. ' .
                    "Expected true, false, or 'mixed' (for tri-state checkboxes), got: '%s'. " .
                    'See: https://www.w3.org/TR/wai-aria-1.2/#aria-checked',
                    $this->tag ?? 'unknown',
                    htmlspecialchars($checked, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        return $this->attr('aria-checked', $checked);
    }

    /**
     * Set the aria-disabled attribute
     *
     * Indicates that an element is perceivable but disabled (not editable or operable).
     *
     * @param bool|Closure $disabled Boolean or closure
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-disabled
     */
    public function ariaDisabled(bool|Closure $disabled = true): static
    {
        $disabled = $this->evaluate($disabled);

        return $this->attr('aria-disabled', $disabled ? 'true' : 'false');
    }

    /**
     * Set the aria-current attribute
     *
     * Indicates the current item within a container or set of related elements.
     * Values: true, false, 'page', 'step', 'location', 'date', 'time'
     *
     * @param string|bool|Closure $value Boolean or string value or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://www.w3.org/TR/wai-aria-1.2/#aria-current
     */
    public function ariaCurrent(string|bool|Closure $value = true): static
    {
        $value = $this->evaluate($value);

        if (is_bool($value)) {
            return $this->attr('aria-current', $value ? 'true' : false);
        }

        if (!in_array($value, ['page', 'step', 'location', 'date', 'time', 'true', 'false'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid value for aria-current attribute on <%s> element. ' .
                    "Expected boolean or one of: 'page', 'step', 'location', 'date', 'time', got: '%s'. " .
                    'This indicates the current item within a container or set of related elements. ' .
                    'See: https://www.w3.org/TR/wai-aria-1.2/#aria-current',
                    $this->tag ?? 'unknown',
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        return $this->attr('aria-current', $value);
    }
}
