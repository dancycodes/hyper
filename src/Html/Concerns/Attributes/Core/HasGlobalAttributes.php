<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Core;

use Closure;

/**
 * Global HTML attributes applicable to all elements
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 *
 * @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
 */
trait HasGlobalAttributes
{
    /**
     * Set the id attribute
     *
     * @param string|Closure $id Element ID or closure returning ID
     */
    public function id(string|Closure $id): static
    {
        return $this->attr('id', $id);
    }

    /**
     * Set the title attribute
     *
     * Provides advisory information about the element (tooltip text).
     *
     * @param string|Closure $title Tooltip text or closure returning text
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#the-title-attribute
     */
    public function title(string|Closure $title): static
    {
        return $this->attr('title', $title);
    }

    /**
     * Set the lang attribute
     *
     * Specifies the primary language for the element's contents.
     *
     * @param string|Closure $lang Language code (e.g., 'en', 'fr', 'es-MX') or closure returning language code
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#the-lang-and-xml:lang-attributes
     */
    public function lang(string|Closure $lang): static
    {
        return $this->attr('lang', $lang);
    }

    /**
     * Set the dir attribute (ltr, rtl, auto)
     *
     * Specifies the text directionality for the element's content.
     *
     * @param string|Closure $direction Text direction ('ltr', 'rtl', 'auto') or closure returning direction
     *
     * @throws \InvalidArgumentException If direction is not 'ltr', 'rtl', or 'auto'
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#the-dir-attribute
     */
    public function dir(string|Closure $direction): static
    {
        $direction = $this->evaluate($direction);

        if (!in_array($direction, ['ltr', 'rtl', 'auto'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid value for dir() attribute on <%s> element. ' .
                    "Expected 'ltr', 'rtl', or 'auto', got: '%s'. " .
                    'See: https://html.spec.whatwg.org/multipage/dom.html#the-dir-attribute',
                    $this->tag ?? 'unknown',
                    htmlspecialchars($direction, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        return $this->attr('dir', $direction);
    }

    /**
     * Set the hidden attribute
     *
     * Indicates that the element is not yet, or is no longer, relevant.
     *
     * @param bool|Closure $hidden Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#the-hidden-attribute
     */
    public function hidden(bool|Closure $hidden = true): static
    {
        return $this->attr('hidden', $hidden);
    }

    /**
     * Set the tabindex attribute
     *
     * Specifies the element's position in the sequential focus navigation order.
     * Positive values create a tabbing order, negative values (typically -1) make
     * element focusable but not reachable via sequential keyboard navigation.
     *
     * @param int|Closure $index Tab index or closure returning index
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#attr-tabindex
     */
    public function tabindex(int|Closure $index): static
    {
        return $this->attr('tabindex', $index);
    }

    /**
     * Set the accesskey attribute
     *
     * Specifies a keyboard shortcut to activate or focus the element.
     *
     * @param string|Closure $key Access key (single character) or closure returning key
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#the-accesskey-attribute
     */
    public function accesskey(string|Closure $key): static
    {
        return $this->attr('accesskey', $key);
    }

    /**
     * Set the style attribute
     *
     * Specifies inline CSS styling for the element.
     * Note: Prefer using CSS classes over inline styles when possible.
     *
     * @param string|Closure $css Inline CSS styles or closure returning CSS
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#the-style-attribute
     */
    public function style(string|Closure $css): static
    {
        return $this->attr('style', $css);
    }

    /**
     * Set the translate attribute
     *
     * Specifies whether the element's content should be translated when the page is localized.
     *
     * @param bool|Closure $translate Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/dom.html#the-translate-attribute
     */
    public function translate(bool|Closure $translate = true): static
    {
        $translate = $this->evaluate($translate);

        return $this->attr('translate', $translate ? 'yes' : 'no');
    }

    /**
     * Set the contenteditable attribute
     *
     * Indicates whether the element's content is editable by the user.
     * Accepts boolean values (true/false) or the string 'plaintext-only'.
     *
     * @param bool|string|Closure $editable Boolean, string ('true', 'false', 'plaintext-only'), or closure
     *
     * @throws \InvalidArgumentException If value is not valid
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#attr-contenteditable
     */
    public function contenteditable(bool|string|Closure $editable = true): static
    {
        $editable = $this->evaluate($editable);

        if (is_bool($editable)) {
            $editable = $editable ? 'true' : 'false';
        } elseif (!in_array($editable, ['true', 'false', 'plaintext-only'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid value for contenteditable() attribute on <%s> element. ' .
                    "Expected true, false, or 'plaintext-only', got: '%s'. " .
                    'See: https://html.spec.whatwg.org/multipage/interaction.html#attr-contenteditable',
                    $this->tag ?? 'unknown',
                    htmlspecialchars($editable, ENT_QUOTES, 'UTF-8')
                )
            );
        }

        return $this->attr('contenteditable', $editable);
    }

    /**
     * Set the draggable attribute
     *
     * Specifies whether the element is draggable using native drag and drop API.
     *
     * @param bool|Closure $draggable Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/dnd.html#the-draggable-attribute
     */
    public function draggable(bool|Closure $draggable = true): static
    {
        $draggable = $this->evaluate($draggable);

        return $this->attr('draggable', $draggable ? 'true' : 'false');
    }

    /**
     * Set the spellcheck attribute
     *
     * Specifies whether the element should have its spelling and grammar checked.
     *
     * @param bool|Closure $spellcheck Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/interaction.html#attr-spellcheck
     */
    public function spellcheck(bool|Closure $spellcheck = true): static
    {
        $spellcheck = $this->evaluate($spellcheck);

        return $this->attr('spellcheck', $spellcheck ? 'true' : 'false');
    }
}
