<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Button extends ContainerElement
{
    use HasFormAttributes;

    public function __construct(?string $text = null)
    {
        parent::__construct('button');
        if ($text) {
            $this->text($text);
        }
    }

    /**
     * Set the type attribute (button, submit, reset)
     *
     * @param string|Closure $type Button type or closure ('submit', 'reset', or 'button')
     */
    public function type(string|Closure $type): static
    {
        return $this->attr('type', $type);
    }
}
