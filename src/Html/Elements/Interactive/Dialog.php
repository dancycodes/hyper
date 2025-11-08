<?php

namespace Dancycodes\Hyper\Html\Elements\Interactive;

use Dancycodes\Hyper\Html\Concerns\Attributes\Interactive\HasInteractiveAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Dialog Element
 *
 * Represents a dialog box or modal window.
 *
 * @see https://html.spec.whatwg.org/multipage/interactive-elements.html#the-dialog-element
 */
class Dialog extends ContainerElement
{
    use HasInteractiveAttributes;

    public function __construct()
    {
        parent::__construct('dialog');
    }
}
