<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Article Element
 *
 * Represents a self-contained composition that could be independently
 * distributed or reused (e.g., blog post, news article, forum post).
 *
 * @see https://html.spec.whatwg.org/multipage/sections.html#the-article-element
 */
class Article extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('article');
    }
}
