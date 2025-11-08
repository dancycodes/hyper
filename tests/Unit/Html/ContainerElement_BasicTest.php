<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test ContainerElement basic functionality
 *
 * Part 1 of 3 - Basic content(), child(), children(), and html() methods
 */
class ContainerElement_BasicTest extends TestCase
{
    public static $latestResponse;

    // ===================================================================
    // CONTENT METHOD TESTS (BASIC)
    // ===================================================================

    /** @test */
    public function test_content_with_single_element_child()
    {
        $html = Html::div()->content(
            Html::p('Paragraph')
        )->render();

        $this->assertStringContainsString('<div>', $html);
        $this->assertStringContainsString('<p>Paragraph</p>', $html);
        $this->assertStringContainsString('</div>', $html);
    }

    /** @test */
    public function test_content_with_multiple_element_children()
    {
        $html = Html::div()->content(
            Html::h1('Title'),
            Html::p('Paragraph 1'),
            Html::p('Paragraph 2')
        )->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Paragraph 1</p>', $html);
        $this->assertStringContainsString('<p>Paragraph 2</p>', $html);
    }

    /** @test */
    public function test_content_with_string_child()
    {
        $html = Html::div()->content('Plain text')->render();

        $this->assertEquals('<div>Plain text</div>', $html);
    }

    /** @test */
    public function test_content_with_multiple_strings()
    {
        $html = Html::div()->content('First', ' ', 'Second')->render();

        $this->assertEquals('<div>First Second</div>', $html);
    }

    /** @test */
    public function test_content_with_mixed_elements_and_strings()
    {
        $html = Html::div()->content(
            'Before',
            Html::strong('Bold'),
            'After'
        )->render();

        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('After', $html);
    }

    /** @test */
    public function test_content_string_children_are_escaped()
    {
        $html = Html::div()->content(
            '<script>alert("XSS")</script>'
        )->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_content_with_array_of_children()
    {
        $children = [
            Html::li('Item 1'),
            Html::li('Item 2'),
            Html::li('Item 3'),
        ];

        $html = Html::ul()->content($children)->render();

        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
        $this->assertStringContainsString('<li>Item 3</li>', $html);
    }

    /** @test */
    public function test_content_with_nested_arrays()
    {
        $html = Html::div()->content([
            Html::h1('Title'),
            [
                Html::p('Paragraph 1'),
                Html::p('Paragraph 2'),
            ],
            Html::footer()->text('Footer'),
        ])->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Paragraph 1</p>', $html);
        $this->assertStringContainsString('<p>Paragraph 2</p>', $html);
        $this->assertStringContainsString('<footer>Footer</footer>', $html);
    }

    /** @test */
    public function test_content_with_null_values_are_skipped()
    {
        $html = Html::div()->content(
            Html::p('First'),
            null,
            Html::p('Second')
        )->render();

        $this->assertStringContainsString('<p>First</p>', $html);
        $this->assertStringContainsString('<p>Second</p>', $html);
    }

    /** @test */
    public function test_content_returns_this_for_chaining()
    {
        $element = Html::div();
        $result = $element->content(Html::p('Test'));

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_content_can_be_called_multiple_times()
    {
        $html = Html::div()
            ->content(Html::h1('Title'))
            ->content(Html::p('Paragraph 1'))
            ->content(Html::p('Paragraph 2'))
            ->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Paragraph 1</p>', $html);
        $this->assertStringContainsString('<p>Paragraph 2</p>', $html);
    }

    // ===================================================================
    // CHILD AND CHILDREN CONVENIENCE METHODS
    // ===================================================================

    /** @test */
    public function test_child_method_adds_single_child()
    {
        $html = Html::div()->child(Html::p('Content'))->render();

        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    /** @test */
    public function test_child_method_can_be_chained()
    {
        $html = Html::div()
            ->child(Html::h1('Title'))
            ->child(Html::p('Paragraph'))
            ->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Paragraph</p>', $html);
    }

    /** @test */
    public function test_children_method_with_array()
    {
        $html = Html::ul()->children([
            Html::li('Item 1'),
            Html::li('Item 2'),
            Html::li('Item 3'),
        ])->render();

        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
        $this->assertStringContainsString('<li>Item 3</li>', $html);
    }

    /** @test */
    public function test_children_method_returns_this_for_chaining()
    {
        $element = Html::div();
        $result = $element->children([Html::p('Test')]);

        $this->assertSame($element, $result);
    }

    // ===================================================================
    // HTML METHOD INTERACTION WITH CHILDREN
    // ===================================================================

    /** @test */
    public function test_html_method_with_raw_markup()
    {
        $html = Html::div()->html('<strong>Bold</strong>')->render();

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
    }

    /** @test */
    public function test_html_and_content_together()
    {
        // Both content() children and html() raw HTML are rendered
        $html = Html::div()
            ->content(Html::p('Paragraph'))
            ->html('<strong>Bold</strong>')
            ->render();

        $this->assertStringContainsString('<p>Paragraph</p>', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
    }

    /** @test */
    public function test_html_method_evaluates_closure()
    {
        $html = Html::div()->html(function () {
            return '<strong>From Closure</strong>';
        })->render();

        $this->assertStringContainsString('<strong>From Closure</strong>', $html);
    }
}
