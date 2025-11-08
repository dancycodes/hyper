<?php

namespace Dancycodes\Hyper\Html\Elements\Embedded;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Portal Element
 *
 * Represents a portal to another HTML page (experimental).
 *
 * @see https://wicg.github.io/portals/
 */
class Portal extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('portal');
    }

    /**
     * Set the src attribute (URL of the portal content)
     *
     * @param string|Closure $url Portal URL or closure
     */
    public function src(string|Closure $url): static
    {
        return $this->attr('src', $url);
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
}
