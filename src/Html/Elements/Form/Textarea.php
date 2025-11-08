<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasFormAttributes;
use Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasInputAttributes;
use Dancycodes\Hyper\Html\Elements\Base\TextElement;

class Textarea extends TextElement
{
    use HasFormAttributes;
    use HasInputAttributes;

    public function __construct(?string $content = null)
    {
        parent::__construct('textarea');
        if ($content) {
            $this->text($content);
        }
    }

    /**
     * Set the rows attribute (visible text lines)
     */
    public function rows(int $rows): static
    {
        return $this->attr('rows', (string) $rows);
    }

    /**
     * Set the cols attribute (visible character width)
     */
    public function cols(int $cols): static
    {
        return $this->attr('cols', (string) $cols);
    }

    /**
     * Set the wrap attribute (how text wraps)
     *
     * @param string $wrap 'soft' (default), 'hard', or 'off'
     */
    public function wrap(string $wrap): static
    {
        return $this->attr('wrap', $wrap);
    }
}
