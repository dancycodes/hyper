<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

class H1 extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('h1');
        if ($text) {
            $this->text($text);
        }
    }
}
