<?php

namespace Dancycodes\Hyper\Html\Elements\Document;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

class Title extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('title');
        if ($text) {
            $this->text($text);
        }
    }
}
