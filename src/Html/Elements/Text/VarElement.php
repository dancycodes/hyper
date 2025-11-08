<?php

namespace Dancycodes\Hyper\Html\Elements\Text;

use Dancycodes\Hyper\Html\Elements\Base\TextElement;

/**
 * Var Element
 *
 * Represents a variable in a mathematical expression or programming context.
 *
 * Note: Named VarElement to avoid PHP reserved keyword 'var'
 *
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-var-element
 */
class VarElement extends TextElement
{
    public function __construct(?string $text = null)
    {
        parent::__construct('var');
        if ($text) {
            $this->text($text);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
