<?php

namespace Dancycodes\Hyper\Html\Elements\Structure;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Div extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('div');
        if ($text) {
            $this->text($text);
        }
    }
}
