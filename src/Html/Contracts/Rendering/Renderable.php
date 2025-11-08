<?php

namespace Dancycodes\Hyper\Html\Contracts\Rendering;

interface Renderable
{
    /**
     * Render the element to HTML string
     */
    public function toHtml(): string;

    /**
     * Render the element with optional layout wrapper
     */
    public function render(mixed $layout = null): mixed;
}
