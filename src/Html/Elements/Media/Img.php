<?php

namespace Dancycodes\Hyper\Html\Elements\Media;

use Dancycodes\Hyper\Html\Concerns\Attributes\Media\HasMediaAttributes;
use Dancycodes\Hyper\Html\Elements\Base\VoidElement;

class Img extends VoidElement
{
    use HasMediaAttributes;

    public function __construct(?string $src = null, ?string $alt = null)
    {
        parent::__construct('img');
        if ($src) {
            $this->src($src);
        }
        if ($alt) {
            $this->alt($alt);
        }
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
