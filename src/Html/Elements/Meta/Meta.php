<?php

namespace Dancycodes\Hyper\Html\Elements\Meta;

use Dancycodes\Hyper\Html\Concerns\Attributes\Meta\HasMetaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

class Meta extends VoidElement
{
    use HasMetaAttributes;

    public function __construct()
    {
        parent::__construct('meta');
    }
}
