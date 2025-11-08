<?php

namespace Dancycodes\Hyper\Html\Elements\Link;

use Dancycodes\Hyper\Html\Concerns\Attributes\Link\HasLinkAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class A extends ContainerElement
{
    use HasLinkAttributes;

    public function __construct(?string $text = null)
    {
        parent::__construct('a');
        if ($text) {
            $this->text($text);
        }
    }
}
