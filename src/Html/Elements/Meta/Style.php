<?php

namespace Dancycodes\Hyper\Html\Elements\Meta;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

class Style extends TextElement
{
    public function __construct(?string $css = null)
    {
        parent::__construct('style');
        // Use html() instead of text() to avoid escaping CSS
        if ($css) {
            $this->html($css);
        }
    }

    /**
     * Set raw CSS content (not escaped)
     *
     * Alias for html() method for clarity. CSS content is NOT escaped.
     *
     * @param string|\Closure $css CSS content or closure
     */
    public function rawContent(string|\Closure $css): static
    {
        return $this->html($css);
    }
}
