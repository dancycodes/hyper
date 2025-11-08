<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class P extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('p');
        if ($text) {
            $this->text($text);
        }
    }
}
