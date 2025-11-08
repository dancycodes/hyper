<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Span extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('span');
        if ($text) {
            $this->text($text);
        }
    }
}
