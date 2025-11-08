<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasInputAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

class Input extends VoidElement
{
    use HasFormAttributes;
    use HasInputAttributes;

    public function __construct()
    {
        parent::__construct('input');
    }
}
