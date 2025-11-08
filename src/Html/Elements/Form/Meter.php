<?php

namespace Dancycodes\Hyper\Html\Elements\Form;

use Closure;
use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;

/**
 * Meter Element
 *
 * Represents a scalar measurement within a known range (gauge).
 *
 * @see https://html.spec.whatwg.org/multipage/form-elements.html#the-meter-element
 */
class Meter extends ContainerElement
{
    public function __construct()
    {
        parent::__construct('meter');
    }

    /**
     * Set the value attribute (current value)
     *
     * @param float|Closure $value Current value or closure
     */
    public function value(float|Closure $value): static
    {
        return $this->attr('value', $value);
    }

    /**
     * Set the min attribute (minimum value)
     *
     * @param float|Closure $min Minimum value or closure
     */
    public function min(float|Closure $min): static
    {
        return $this->attr('min', $min);
    }

    /**
     * Set the max attribute (maximum value)
     *
     * @param float|Closure $max Maximum value or closure
     */
    public function max(float|Closure $max): static
    {
        return $this->attr('max', $max);
    }

    /**
     * Set the low attribute (low threshold)
     *
     * @param float|Closure $low Low threshold or closure
     */
    public function low(float|Closure $low): static
    {
        return $this->attr('low', $low);
    }

    /**
     * Set the high attribute (high threshold)
     *
     * @param float|Closure $high High threshold or closure
     */
    public function high(float|Closure $high): static
    {
        return $this->attr('high', $high);
    }

    /**
     * Set the optimum attribute (optimal value)
     *
     * @param float|Closure $optimum Optimal value or closure
     */
    public function optimum(float|Closure $optimum): static
    {
        return $this->attr('optimum', $optimum);
    }
}
