<?php

namespace Dancycodes\Hyper\Html\Contracts\Structure;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\Element;

interface HasChildren
{
    /**
     * Add content to the element (text, elements, arrays, or closures)
     *
     * Accepts:
     * - Closures (evaluated with dependency injection)
     * - Strings (escaped as text)
     * - Element instances
     * - Arrays of elements
     * - Mixed variadic arguments
     */
    public function content(string|Element|array|Closure ...$items): static;

    /**
     * Add raw HTML content (dangerous - no escaping)
     *
     * Accepts closures for dynamic HTML generation.
     */
    public function html(string|Closure $html): static;
}
