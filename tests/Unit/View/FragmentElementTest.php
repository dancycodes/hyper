<?php

namespace Dancycodes\Hyper\Tests\Unit\View;

use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\View\Fragment\CloseFragmentElement;
use Dancycodes\Hyper\View\Fragment\FragmentElement;
use Dancycodes\Hyper\View\Fragment\OpenFragmentElement;

/**
 * Test the Fragment Element classes
 *
 * @see TESTING.md - Files 9-11: Fragment Element Tests
 * Status: 🔄 IN PROGRESS - 10 test methods
 */
class FragmentElementTest extends TestCase
{
    public static $latestResponse;

    // ==========================================
    // OpenFragmentElement Tests (4 methods)
    // ==========================================

    /** @test */
    public function test_open_fragment_element_has_name_property()
    {
        $element = new OpenFragmentElement;
        $element->name = 'test-fragment';

        $this->assertEquals('test-fragment', $element->name);
    }

    /** @test */
    public function test_open_fragment_element_has_start_offset()
    {
        $element = new OpenFragmentElement;
        $element->startOffset = 10;

        $this->assertEquals(10, $element->startOffset);
    }

    /** @test */
    public function test_open_fragment_element_has_end_offset()
    {
        $element = new OpenFragmentElement;
        $element->endOffset = 50;

        $this->assertEquals(50, $element->endOffset);
    }

    /** @test */
    public function test_open_fragment_element_extends_fragment_element()
    {
        $element = new OpenFragmentElement;

        $this->assertInstanceOf(FragmentElement::class, $element);
    }

    // ==========================================
    // CloseFragmentElement Tests (3 methods)
    // ==========================================

    /** @test */
    public function test_close_fragment_element_has_start_offset()
    {
        $element = new CloseFragmentElement;
        $element->startOffset = 100;

        $this->assertEquals(100, $element->startOffset);
    }

    /** @test */
    public function test_close_fragment_element_has_end_offset()
    {
        $element = new CloseFragmentElement;
        $element->endOffset = 150;

        $this->assertEquals(150, $element->endOffset);
    }

    /** @test */
    public function test_close_fragment_element_extends_fragment_element()
    {
        $element = new CloseFragmentElement;

        $this->assertInstanceOf(FragmentElement::class, $element);
    }

    // ==========================================
    // FragmentElement Base Class Tests (3 methods)
    // ==========================================

    /** @test */
    public function test_fragment_element_default_start_offset_is_zero()
    {
        $element = new OpenFragmentElement;

        $this->assertEquals(0, $element->startOffset);
    }

    /** @test */
    public function test_fragment_element_default_end_offset_is_zero()
    {
        $element = new CloseFragmentElement;

        $this->assertEquals(0, $element->endOffset);
    }

    /** @test */
    public function test_fragment_elements_are_mutable()
    {
        $element = new OpenFragmentElement;

        // Set initial values
        $element->startOffset = 10;
        $element->endOffset = 20;
        $element->name = 'initial';

        // Mutate values
        $element->startOffset = 100;
        $element->endOffset = 200;
        $element->name = 'changed';

        $this->assertEquals(100, $element->startOffset);
        $this->assertEquals(200, $element->endOffset);
        $this->assertEquals('changed', $element->name);
    }
}
