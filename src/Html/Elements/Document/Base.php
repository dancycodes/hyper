<?php

namespace Dancycodes\Hyper\Html\Elements\Document;

use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

class Base extends VoidElement
{
    public function __construct()
    {
        parent::__construct('base');
    }

    /**
     * Set the href attribute (base URL)
     *
     * @param string|Closure $url Base URL or closure
     */
    public function href(string|Closure $url): static
    {
        return $this->attr('href', $url);
    }

    /**
     * Set the target attribute (default target for links)
     *
     * @param string|Closure $target Target value or closure
     */
    public function target(string|Closure $target): static
    {
        return $this->attr('target', $target);
    }
}
