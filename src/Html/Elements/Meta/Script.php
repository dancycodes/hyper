<?php

namespace Dancycodes\Hyper\Html\Elements\Meta;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

class Script extends TextElement
{
    public function __construct(?string $content = null)
    {
        parent::__construct('script');
        // Use html() instead of text() to avoid escaping JavaScript
        if ($content) {
            $this->html($content);
        }
    }

    /**
     * Set raw JavaScript content (not escaped)
     *
     * Alias for html() method for clarity. JavaScript content is NOT escaped.
     *
     * @param string|\Closure $content JavaScript content or closure
     */
    public function rawContent(string|\Closure $content): static
    {
        return $this->html($content);
    }

    /**
     * Set the src attribute (external script)
     *
     * @param string|Closure $url Script URL or closure
     */
    public function src(string|Closure $url): static
    {
        return $this->attr('src', $url);
    }

    /**
     * Set the type attribute
     *
     * @param string|Closure $type MIME type or closure
     */
    public function type(string|Closure $type): static
    {
        return $this->attr('type', $type);
    }

    /**
     * Set the async attribute
     *
     * @param bool|Closure $async Async loading or closure
     */
    public function async(bool|Closure $async = true): static
    {
        return $this->attr('async', $async);
    }

    /**
     * Set the defer attribute
     *
     * @param bool|Closure $defer Defer execution or closure
     */
    public function defer(bool|Closure $defer = true): static
    {
        return $this->attr('defer', $defer);
    }
}
