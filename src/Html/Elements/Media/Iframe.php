<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Iframe Element
 *
 * Represents a nested browsing context (embedded page).
 *
 * @see https://html.spec.whatwg.org/multipage/iframe-embed-object.html#the-iframe-element
 */
class Iframe extends ContainerElement
{
    use HasMediaAttributes;

    public function __construct()
    {
        parent::__construct('iframe');
    }

    /**
     * Set the name attribute (frame name for targeting)
     *
     * @param string|Closure $name Frame name or closure
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }

    /**
     * Set the sandbox attribute (security restrictions)
     *
     * @param string|Closure $permissions Sandbox permissions or closure
     */
    public function sandbox(string|Closure $permissions = ''): static
    {
        return $this->attr('sandbox', $permissions);
    }

    /**
     * Set the allow attribute (feature policy)
     *
     * @param string|Closure $policy Feature policy or closure
     */
    public function allow(string|Closure $policy): static
    {
        return $this->attr('allow', $policy);
    }

    /**
     * Set the referrerpolicy attribute
     *
     * @param string|Closure $policy Referrer policy or closure
     */
    public function referrerpolicy(string|Closure $policy): static
    {
        return $this->attr('referrerpolicy', $policy);
    }

    // Note: loading() is provided by the HasMediaAttributes trait
}
