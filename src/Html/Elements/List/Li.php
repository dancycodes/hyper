<?php

namespace Dancycodes\Hyper\Html\Elements\List;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Li extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('li');
        if ($text) {
            $this->text($text);
        }
    }
}
