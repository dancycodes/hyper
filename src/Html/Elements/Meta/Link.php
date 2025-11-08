<?php

namespace Dancycodes\Hyper\Html\Elements\Meta;

use Dancycodes\Hyper\Html\Concerns\Attributes\Link\HasLinkAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Meta\HasMetaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

class Link extends VoidElement
{
    use HasLinkAttributes;
    use HasMetaAttributes;

    public function __construct()
    {
        parent::__construct('link');
    }
}
