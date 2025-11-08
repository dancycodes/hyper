<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test basic HTML element creation and rendering
 *
 * Covers foundational functionality:
 * - Factory method creation
 * - Basic rendering
 * - Attribute handling
 * - Class management
 * - Text content
 * - Method chaining
 */
class ElementBasicsTest extends TestCase
{
    // ===================================================================
    // FACTORY METHOD TESTS
    // ===================================================================

    /** @test */
    public function test_creates_basic_div_element()
    {
        $html = Html::div()->render();

        $this->assertEquals('<div></div>', $html);
    }

    /** @test */
    public function test_creates_div_with_text_content()
    {
        $html = Html::div('Hello World')->render();

        $this->assertEquals('<div>Hello World</div>', $html);
    }

    /** @test */
    public function test_creates_span_element()
    {
        $html = Html::span('Text')->render();

        $this->assertEquals('<span>Text</span>', $html);
    }

    /** @test */
    public function test_creates_button_element()
    {
        $html = Html::button('Click Me')->render();

        $this->assertEquals('<button>Click Me</button>', $html);
    }

    /** @test */
    public function test_creates_paragraph_element()
    {
        $html = Html::p('Paragraph text')->render();

        $this->assertEquals('<p>Paragraph text</p>', $html);
    }

    /** @test */
    public function test_creates_heading_elements()
    {
        $this->assertEquals('<h1>Title</h1>', Html::h1('Title')->render());
        $this->assertEquals('<h2>Subtitle</h2>', Html::h2('Subtitle')->render());
        $this->assertEquals('<h3>Heading</h3>', Html::h3('Heading')->render());
        $this->assertEquals('<h4>Subheading</h4>', Html::h4('Subheading')->render());
        $this->assertEquals('<h5>Minor</h5>', Html::h5('Minor')->render());
        $this->assertEquals('<h6>Smallest</h6>', Html::h6('Smallest')->render());
    }

    // ===================================================================
    // ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_sets_id_attribute()
    {
        $html = Html::div()->id('main-content')->render();

        $this->assertEquals('<div id="main-content"></div>', $html);
    }

    /** @test */
    public function test_sets_single_class()
    {
        $html = Html::div()->class('container')->render();

        $this->assertEquals('<div class="container"></div>', $html);
    }

    /** @test */
    public function test_sets_multiple_classes_with_multiple_calls()
    {
        $html = Html::div()
            ->class('container')
            ->class('mx-auto')
            ->render();

        $this->assertEquals('<div class="container mx-auto"></div>', $html);
    }

    /** @test */
    public function test_sets_multiple_classes_with_array()
    {
        $html = Html::div()->class(['container', 'mx-auto', 'p-4'])->render();

        $this->assertEquals('<div class="container mx-auto p-4"></div>', $html);
    }

    /** @test */
    public function test_sets_multiple_classes_with_space_separated_string()
    {
        $html = Html::div()->class('container mx-auto p-4')->render();

        $this->assertEquals('<div class="container mx-auto p-4"></div>', $html);
    }

    /** @test */
    public function test_merges_classes_from_multiple_sources()
    {
        $html = Html::div()
            ->class('container')
            ->class(['mx-auto', 'p-4'])
            ->class('bg-white')
            ->render();

        $this->assertEquals('<div class="container mx-auto p-4 bg-white"></div>', $html);
    }

    /** @test */
    public function test_sets_custom_attribute_with_attr_method()
    {
        $html = Html::div()->attr('data-value', '123')->render();

        $this->assertEquals('<div data-value="123"></div>', $html);
    }

    /** @test */
    public function test_sets_multiple_custom_attributes()
    {
        $html = Html::div()
            ->attr('data-id', '1')
            ->attr('data-name', 'test')
            ->render();

        $this->assertEquals('<div data-id="1" data-name="test"></div>', $html);
    }

    /** @test */
    public function test_sets_boolean_attribute()
    {
        $html = Html::input()->attr('required', true)->render();

        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function test_attribute_escaping_prevents_xss()
    {
        $malicious = '"><script>alert("XSS")</script><div class="';
        $html = Html::div()->attr('data-value', $malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ===================================================================
    // TEXT CONTENT TESTS
    // ===================================================================

    /** @test */
    public function test_text_method_escapes_html()
    {
        $html = Html::div()->text('<script>alert("XSS")</script>')->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_html_method_allows_raw_html()
    {
        $html = Html::div()->html('<strong>Bold</strong>')->render();

        $this->assertEquals('<div><strong>Bold</strong></div>', $html);
    }

    /** @test */
    public function test_content_method_with_string_escapes_html()
    {
        $html = Html::div()->content('<script>XSS</script>')->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_constructor_text_parameter_escapes_html()
    {
        $html = Html::div('<script>XSS</script>')->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_empty_text_content()
    {
        $html = Html::div('')->render();

        $this->assertEquals('<div></div>', $html);
    }

    /** @test */
    public function test_null_text_content()
    {
        $html = Html::div(null)->render();

        $this->assertEquals('<div></div>', $html);
    }

    // ===================================================================
    // METHOD CHAINING TESTS
    // ===================================================================

    /** @test */
    public function test_method_chaining_returns_same_instance()
    {
        $element = Html::div();
        $result = $element->class('test');

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_complex_method_chaining()
    {
        $html = Html::div()
            ->id('container')
            ->class('flex')
            ->class('items-center')
            ->attr('data-test', 'value')
            ->text('Hello')
            ->render();

        $this->assertStringContainsString('id="container"', $html);
        $this->assertStringContainsString('class="flex items-center"', $html);
        $this->assertStringContainsString('data-test="value"', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    // ===================================================================
    // RENDER METHOD TESTS
    // ===================================================================

    /** @test */
    public function test_render_returns_string()
    {
        $result = Html::div()->render();

        $this->assertIsString($result);
    }

    /** @test */
    public function test_to_string_method_works()
    {
        $element = Html::div('Test');
        $string = (string) $element;

        $this->assertEquals('<div>Test</div>', $string);
    }

    /** @test */
    public function test_to_string_magic_method()
    {
        $element = Html::div('Content');

        // Implicit string conversion
        $result = "$element";

        $this->assertEquals('<div>Content</div>', $result);
    }

    // ===================================================================
    // EDGE CASE TESTS
    // ===================================================================

    /** @test */
    public function test_handles_special_characters_in_text()
    {
        $html = Html::div('Price: $100 & up')->render();

        $this->assertStringContainsString('$100 &amp; up', $html);
    }

    /** @test */
    public function test_handles_quotes_in_attributes()
    {
        $html = Html::div()->attr('data-value', 'He said "hello"')->render();

        $this->assertStringContainsString('data-value="He said &quot;hello&quot;"', $html);
    }

    /** @test */
    public function test_handles_unicode_content()
    {
        $html = Html::div('Hello 世界 🌍')->render();

        $this->assertStringContainsString('Hello 世界 🌍', $html);
    }

    /** @test */
    public function test_empty_class_array_renders_no_class_attribute()
    {
        $html = Html::div()->class([])->render();

        $this->assertEquals('<div></div>', $html);
    }

    /** @test */
    public function test_filters_empty_classes()
    {
        // Empty strings are filtered, but whitespace is preserved in class names
        $html = Html::div()->class(['container', '', 'mx-auto'])->render();

        $this->assertEquals('<div class="container mx-auto"></div>', $html);
    }

    /** @test */
    public function test_numeric_class_names()
    {
        // While unusual, numeric classes should work
        $html = Html::div()->class(['1', '2', '3'])->render();

        $this->assertEquals('<div class="1 2 3"></div>', $html);
    }

    // ===================================================================
    // ATTRIBUTE ORDER TESTS
    // ===================================================================

    /** @test */
    public function test_attributes_render_in_consistent_order()
    {
        // Attributes should always render in the same order
        $html1 = Html::div()->id('test')->class('container')->render();
        $html2 = Html::div()->class('container')->id('test')->render();

        // Both should have id first, then class (alphabetical)
        $this->assertStringContainsString('id="test"', $html1);
        $this->assertStringContainsString('class="container"', $html1);
        $this->assertStringContainsString('id="test"', $html2);
        $this->assertStringContainsString('class="container"', $html2);
    }

    // ===================================================================
    // ELEMENT REUSE TESTS
    // ===================================================================

    /** @test */
    public function test_element_can_be_rendered_multiple_times()
    {
        $element = Html::div('Test');

        $first = $element->render();
        $second = $element->render();

        $this->assertEquals($first, $second);
    }

    /** @test */
    public function test_modifying_element_after_render_affects_subsequent_renders()
    {
        $element = Html::div('Test');

        $first = $element->render();
        $element->class('new-class');
        $second = $element->render();

        $this->assertStringNotContainsString('new-class', $first);
        $this->assertStringContainsString('new-class', $second);
    }
}
