<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Form extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('form');
    }

    /**
     * Set the action attribute (form submission URL)
     *
     * @param string|Closure $url Form action URL or closure
     */
    public function action(string|Closure $url): static
    {
        return $this->attr('action', $url);
    }

    /**
     * Set the method attribute (HTTP method)
     *
     * @param string|Closure $method HTTP method ('GET' or 'POST') or closure
     */
    public function method(string|Closure $method): static
    {
        return $this->attr('method', $method);
    }

    /**
     * Set the enctype attribute (form data encoding type)
     *
     * Common values:
     * - 'application/x-www-form-urlencoded' (default)
     * - 'multipart/form-data' (required for file uploads)
     * - 'text/plain'
     *
     * @param string|Closure $type Encoding type or closure
     */
    public function enctype(string|Closure $type): static
    {
        return $this->attr('enctype', $type);
    }

    /**
     * Set the novalidate attribute (disable HTML5 validation)
     *
     * @param bool|Closure $novalidate Disable validation or closure
     */
    public function novalidate(bool|Closure $novalidate = true): static
    {
        if ($novalidate === true || ($novalidate instanceof Closure)) {
            return $this->attr('novalidate', $novalidate);
        }

        return $this;
    }

    /**
     * Set the target attribute (where to display response)
     *
     * Common values: '_self', '_blank', '_parent', '_top'
     *
     * @param string|Closure $target Target context or closure
     */
    public function target(string|Closure $target): static
    {
        return $this->attr('target', $target);
    }
}
