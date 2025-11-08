<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test TextElement behavior for text content and HTML content
 *
 * TextElement is the base class for elements that can contain text or HTML content.
 * Tests cover:
 * - text() method (escaped content)
 * - html() method (raw HTML)
 * - Closure evaluation
 * - XSS protection
 * - Content precedence rules
 * - Method chaining
 */
class TextElementTest extends TestCase
{
    // ===================================================================
    // TEXT METHOD TESTS
    // ===================================================================

    /** @test */
    public function test_text_method_sets_text_content()
    {
        $html = Html::p()->text('Hello World')->render();

        $this->assertEquals('<p>Hello World</p>', $html);
    }

    /** @test */
    public function test_text_method_escapes_html_tags()
    {
        $html = Html::p()->text('<script>alert("XSS")</script>')->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;/script&gt;', $html);
    }

    /** @test */
    public function test_text_method_escapes_special_characters()
    {
        $html = Html::p()->text('Price: $100 & up')->render();

        $this->assertStringContainsString('$100 &amp; up', $html);
    }

    /** @test */
    public function test_text_method_escapes_quotes()
    {
        $html = Html::p()->text('He said "hello"')->render();

        $this->assertStringContainsString('He said &quot;hello&quot;', $html);
    }

    /** @test */
    public function test_text_method_escapes_apostrophes()
    {
        $html = Html::p()->text("It's working")->render();

        $this->assertStringContainsString('It&#039;s working', $html);
    }

    /** @test */
    public function test_text_method_escapes_less_than()
    {
        $html = Html::p()->text('5 < 10')->render();

        $this->assertStringContainsString('5 &lt; 10', $html);
    }

    /** @test */
    public function test_text_method_escapes_greater_than()
    {
        $html = Html::p()->text('10 > 5')->render();

        $this->assertStringContainsString('10 &gt; 5', $html);
    }

    /** @test */
    public function test_text_method_handles_unicode()
    {
        $html = Html::p()->text('Hello 世界 🌍')->render();

        $this->assertStringContainsString('Hello 世界 🌍', $html);
    }

    /** @test */
    public function test_text_method_handles_emoji()
    {
        $html = Html::p()->text('👍 😀 🎉')->render();

        $this->assertStringContainsString('👍 😀 🎉', $html);
    }

    /** @test */
    public function test_text_method_with_empty_string()
    {
        $html = Html::p()->text('')->render();

        $this->assertEquals('<p></p>', $html);
    }

    /** @test */
    public function test_text_method_returns_this_for_chaining()
    {
        $element = Html::p();
        $result = $element->text('Test');

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_text_method_with_multiline_content()
    {
        $content = "Line 1\nLine 2\nLine 3";
        $html = Html::pre()->text($content)->render();

        $this->assertStringContainsString('Line 1', $html);
        $this->assertStringContainsString('Line 2', $html);
        $this->assertStringContainsString('Line 3', $html);
    }

    // ===================================================================
    // HTML METHOD TESTS
    // ===================================================================

    /** @test */
    public function test_html_method_sets_raw_html()
    {
        $html = Html::div()->html('<strong>Bold</strong>')->render();

        $this->assertEquals('<div><strong>Bold</strong></div>', $html);
    }

    /** @test */
    public function test_html_method_does_not_escape()
    {
        $html = Html::div()->html('<script>alert("test")</script>')->render();

        // html() method intentionally does NOT escape (dangerous but sometimes needed)
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    /** @test */
    public function test_html_method_with_complex_markup()
    {
        $markup = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $html = Html::div()->html($markup)->render();

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
    }

    /** @test */
    public function test_html_method_with_empty_string()
    {
        $html = Html::div()->html('')->render();

        $this->assertEquals('<div></div>', $html);
    }

    /** @test */
    public function test_html_method_returns_this_for_chaining()
    {
        $element = Html::div();
        $result = $element->html('<strong>Test</strong>');

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_html_method_with_self_closing_tags()
    {
        $html = Html::div()->html('Before<br>After<hr>End')->render();

        $this->assertStringContainsString('Before<br>After<hr>End', $html);
    }

    // ===================================================================
    // CLOSURE EVALUATION TESTS
    // ===================================================================

    /** @test */
    public function test_text_method_evaluates_closure()
    {
        $html = Html::p()->text(function () {
            return 'From Closure';
        })->render();

        $this->assertStringContainsString('From Closure', $html);
    }

    /** @test */
    public function test_text_closure_result_is_escaped()
    {
        $html = Html::p()->text(function () {
            return '<script>alert("XSS")</script>';
        })->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_html_method_evaluates_closure()
    {
        $html = Html::div()->html(function () {
            return '<strong>Bold from closure</strong>';
        })->render();

        $this->assertStringContainsString('<strong>Bold from closure</strong>', $html);
    }

    /** @test */
    public function test_html_closure_result_is_not_escaped()
    {
        $html = Html::div()->html(function () {
            return '<strong>Bold</strong>';
        })->render();

        // Should NOT be escaped
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringNotContainsString('&lt;strong&gt;', $html);
    }

    /** @test */
    public function test_closure_with_dependency_injection()
    {
        // Closure should have access to Laravel container
        $html = Html::p()->text(function () {
            $config = app('config');

            return 'App name: ' . $config->get('app.name', 'Laravel');
        })->render();

        $this->assertStringContainsString('App name:', $html);
    }

    // ===================================================================
    // XSS PROTECTION TESTS
    // ===================================================================

    /** @test */
    public function test_text_prevents_xss_via_script_tags()
    {
        $malicious = '<script>document.cookie</script>';
        $html = Html::p()->text($malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_text_prevents_xss_via_img_onerror()
    {
        $malicious = '<img src=x onerror="alert(1)">';
        $html = Html::p()->text($malicious)->render();

        // Tags are escaped so they can't execute
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('&quot;', $html); // Quotes are escaped
    }

    /** @test */
    public function test_text_prevents_xss_via_javascript_protocol()
    {
        $malicious = 'javascript:alert(document.cookie)';
        $html = Html::p()->text($malicious)->render();

        // Text is rendered as-is (no special protocol filtering needed for text content)
        $this->assertStringContainsString('javascript:alert(document.cookie)', $html);
    }

    /** @test */
    public function test_text_prevents_xss_via_event_handlers()
    {
        $malicious = '<div onclick="malicious()">Click</div>';
        $html = Html::p()->text($malicious)->render();

        // Tags are escaped so handlers can't execute
        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringContainsString('&lt;div', $html);
    }

    /** @test */
    public function test_html_does_not_prevent_xss_by_design()
    {
        // This test documents the intentional behavior - html() is dangerous
        $malicious = '<script>alert("XSS")</script>';
        $html = Html::div()->html($malicious)->render();

        // html() method WILL include the script tag (by design)
        $this->assertStringContainsString('<script>', $html);

        // This is intentional - developers must use html() carefully with trusted input only
    }

    // ===================================================================
    // ELEMENT-SPECIFIC TESTS
    // ===================================================================

    /** @test */
    public function test_heading_elements_with_text()
    {
        $this->assertEquals('<h1>Title</h1>', Html::h1()->text('Title')->render());
        $this->assertEquals('<h2>Subtitle</h2>', Html::h2()->text('Subtitle')->render());
        $this->assertEquals('<h3>Heading</h3>', Html::h3()->text('Heading')->render());
        $this->assertEquals('<h4>Subheading</h4>', Html::h4()->text('Subheading')->render());
        $this->assertEquals('<h5>Minor</h5>', Html::h5()->text('Minor')->render());
        $this->assertEquals('<h6>Smallest</h6>', Html::h6()->text('Smallest')->render());
    }

    /** @test */
    public function test_paragraph_with_text()
    {
        $html = Html::p()->text('Paragraph content')->render();

        $this->assertEquals('<p>Paragraph content</p>', $html);
    }

    /** @test */
    public function test_span_with_text()
    {
        $html = Html::span()->text('Inline text')->render();

        $this->assertEquals('<span>Inline text</span>', $html);
    }

    /** @test */
    public function test_strong_with_text()
    {
        $html = Html::strong()->text('Important')->render();

        $this->assertEquals('<strong>Important</strong>', $html);
    }

    /** @test */
    public function test_em_with_text()
    {
        $html = Html::em()->text('Emphasized')->render();

        $this->assertEquals('<em>Emphasized</em>', $html);
    }

    /** @test */
    public function test_code_with_text()
    {
        $html = Html::code()->text('$variable = "value";')->render();

        // Code content should be escaped
        $this->assertStringContainsString('$variable = &quot;value&quot;', $html);
    }

    /** @test */
    public function test_pre_with_text()
    {
        $code = "function test() {\n    return true;\n}";
        $html = Html::pre()->text($code)->render();

        $this->assertStringContainsString('function test()', $html);
    }

    /** @test */
    public function test_blockquote_with_text()
    {
        $html = Html::blockquote()->text('A wise quote')->render();

        $this->assertEquals('<blockquote>A wise quote</blockquote>', $html);
    }

    // ===================================================================
    // METHOD CHAINING WITH ATTRIBUTES TESTS
    // ===================================================================

    /** @test */
    public function test_text_with_attributes()
    {
        $html = Html::p()
            ->class('highlight')
            ->id('quote')
            ->text('Quote text')
            ->render();

        $this->assertStringContainsString('class="highlight"', $html);
        $this->assertStringContainsString('id="quote"', $html);
        $this->assertStringContainsString('Quote text', $html);
    }

    /** @test */
    public function test_html_with_attributes()
    {
        $html = Html::div()
            ->class('container')
            ->html('<strong>Bold</strong>')
            ->render();

        $this->assertStringContainsString('class="container"', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
    }

    /** @test */
    public function test_complex_chaining()
    {
        $html = Html::p()
            ->id('intro')
            ->class('text-lg')
            ->attr('data-value', '123')
            ->text('Introduction paragraph')
            ->render();

        $this->assertStringContainsString('id="intro"', $html);
        $this->assertStringContainsString('class="text-lg"', $html);
        $this->assertStringContainsString('data-value="123"', $html);
        $this->assertStringContainsString('Introduction paragraph', $html);
    }

    // ===================================================================
    // EDGE CASES
    // ===================================================================

    /** @test */
    public function test_very_long_text_content()
    {
        $longText = str_repeat('A', 10000);
        $html = Html::p()->text($longText)->render();

        $this->assertStringContainsString($longText, $html);
        $this->assertEquals(strlen($longText) + strlen('<p></p>'), strlen($html));
    }

    /** @test */
    public function test_text_with_null_bytes()
    {
        $input = "text\x00with\x00nulls";
        $html = Html::p()->text($input)->render();

        // Null bytes should be handled safely
        $this->assertIsString($html);
        $this->assertStringContainsString('<p>', $html);
    }

    /** @test */
    public function test_html_with_malformed_markup()
    {
        // html() doesn't validate - just passes through
        $malformed = '<div><p>Not closed';
        $html = Html::div()->html($malformed)->render();

        $this->assertStringContainsString($malformed, $html);
    }

    /** @test */
    public function test_whitespace_preservation()
    {
        $text = '  Leading and trailing spaces  ';
        $html = Html::span()->text($text)->render();

        $this->assertStringContainsString('  Leading and trailing spaces  ', $html);
    }

    /** @test */
    public function test_newline_preservation()
    {
        $text = "Line 1\n\nLine 2";
        $html = Html::pre()->text($text)->render();

        // Newlines should be preserved in output
        $this->assertStringContainsString("Line 1\n\nLine 2", $html);
    }

    /** @test */
    public function test_tab_preservation()
    {
        $text = "Indented\twith\ttabs";
        $html = Html::pre()->text($text)->render();

        $this->assertStringContainsString("Indented\twith\ttabs", $html);
    }

    /** @test */
    public function test_zero_content()
    {
        $html = Html::p()->text('0')->render();

        $this->assertEquals('<p>0</p>', $html);
    }

    /** @test */
    public function test_false_like_content()
    {
        // String '0' should render
        $html = Html::span()->text('0')->render();
        $this->assertEquals('<span>0</span>', $html);

        // Empty string should render as empty element
        $html = Html::span()->text('')->render();
        $this->assertEquals('<span></span>', $html);
    }

    // ===================================================================
    // SPECIAL HTML ENTITIES
    // ===================================================================

    /** @test */
    public function test_already_encoded_entities_are_double_escaped()
    {
        // If user passes already-encoded entities, they get double-escaped
        $input = '&lt;script&gt;';
        $html = Html::p()->text($input)->render();

        // Should be double-escaped to prevent entity injection
        $this->assertStringContainsString('&amp;lt;script&amp;gt;', $html);
    }

    /** @test */
    public function test_html_numeric_entities()
    {
        $input = '&#60;script&#62;';
        $html = Html::p()->text($input)->render();

        // Numeric entities should be escaped
        $this->assertStringContainsString('&amp;#60;', $html);
    }

    /** @test */
    public function test_mixed_special_characters()
    {
        $input = '<tag attr="value" & \'quote\'>';
        $html = Html::p()->text($input)->render();

        $this->assertStringContainsString('&lt;tag', $html);
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&#039;', $html);
    }
}
