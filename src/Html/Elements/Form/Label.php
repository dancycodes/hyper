<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

class Label extends ContainerElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('label');
        if ($text) {
            $this->text($text);
        }
    }

    /**
     * Set the for attribute
     *
     * @param string|Closure $inputId Input element ID or closure
     */
    public function for(string|Closure $inputId): static
    {
        return $this->attr('for', $inputId);
    }
}
