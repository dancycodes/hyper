<?php

namespace Dancycodes\Hyper\Html\Elements\Document;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Html extends ContainerElement
{
    /**
     * Whether to prepend the DOCTYPE declaration
     */
    protected bool $includeDoctype = false;

    public function __construct()
    {
        parent::__construct('html');
    }

    /**
     * Set the lang attribute
     *
     * @param string|Closure $lang Language code or closure
     */
    public function lang(string|Closure $lang): static
    {
        return $this->attr('lang', $lang);
    }

    /**
     * Set the xmlns attribute
     *
     * @param string|Closure $namespace XML namespace or closure
     */
    public function xmlns(string|Closure $namespace): static
    {
        return $this->attr('xmlns', $namespace);
    }

    /**
     * Include the HTML5 DOCTYPE declaration before the <html> tag
     *
     * When enabled, the rendered output will be:
     * <!DOCTYPE html>
     * <html>...</html>
     */
    public function withDoctype(): static
    {
        $this->includeDoctype = true;

        return $this;
    }

    /**
     * Render the HTML element with optional DOCTYPE declaration
     */
    public function toHtml(): string
    {
        $html = parent::toHtml();

        if ($this->includeDoctype) {
            return "<!DOCTYPE html>\n" . $html;
        }

        return $html;
    }
}
