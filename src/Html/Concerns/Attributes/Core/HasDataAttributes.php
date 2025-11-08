<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Core;

use Closure;

/**
 * Standard HTML data-* attributes
 *
 * The $value parameter already accepts mixed type, closures are evaluated within attr()
 *
 * @see https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes
 */
trait HasDataAttributes
{
    /**
     * Set a custom data attribute
     *
     * Note: $name remains string (attribute name itself shouldn't be dynamic typically)
     *       $value accepts mixed including closures which are evaluated within attr()
     *
     * @param string $name Attribute name without 'data-' prefix (camelCase or kebab-case)
     * @param mixed $value Value (can be string, int, bool, Closure, etc.)
     */
    public function dataAttribute(string $name, mixed $value): static
    {
        // Convert camelCase to kebab-case for HTML attribute names
        // Examples: userId -> user-id, userName -> user-name
        $kebabName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));

        // Sets: data-{$kebabName}="{$value}"
        return $this->attr("data-{$kebabName}", $value);
    }
}
