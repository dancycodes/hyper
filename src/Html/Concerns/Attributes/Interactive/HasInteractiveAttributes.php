<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Interactive;

use Closure;

/**
 * Interactive element-specific attributes
 *
 * Note: contenteditable, draggable, and spellcheck are global attributes
 * and have been moved to HasGlobalAttributes trait.
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasInteractiveAttributes
{
    /**
     * Set the open attribute (for details and dialog elements)
     *
     * Specifies that the details/dialog should be visible (open) to the user.
     * - For <details>: Content is expanded by default
     * - For <dialog>: Dialog is shown by default
     *
     * @param bool|Closure $open Boolean or closure returning boolean
     *
     * @see https://html.spec.whatwg.org/multipage/interactive-elements.html#attr-details-open
     */
    public function open(bool|Closure $open = true): static
    {
        return $this->attr('open', $open);
    }
}
