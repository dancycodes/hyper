<?php

namespace Dancycodes\Hyper\Tests\Unit\View;

use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\View\Fragment\BladeFragmentParser;
use Dancycodes\Hyper\View\Fragment\CloseFragmentElement;
use Dancycodes\Hyper\View\Fragment\OpenFragmentElement;

/**
 * Test the BladeFragmentParser class
 *
 * @see TESTING.md - File 8: BladeFragmentParser Tests
 * Status: 🔄 IN PROGRESS - 12 test methods
 */
class BladeFragmentParserTest extends TestCase
{
    public static $latestResponse;

    protected BladeFragmentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BladeFragmentParser('fragment', 'endfragment');
    }

    // ==========================================
    // Parsing Tests (6 methods)
    // ==========================================

    /** @test */
    public function test_parse_detects_fragment_directives()
    {
        $content = "@fragment('header')\n<h1>Title</h1>\n@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertCount(2, $elements);
        $this->assertInstanceOf(OpenFragmentElement::class, $elements[0]);
        $this->assertEquals('header', $elements[0]->name);
    }

    /** @test */
    public function test_parse_detects_endfragment_directives()
    {
        $content = "@fragment('test')\nContent\n@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertCount(2, $elements);
        $this->assertInstanceOf(CloseFragmentElement::class, $elements[1]);
    }

    /** @test */
    public function test_parse_handles_multiple_fragments()
    {
        $content = "@fragment('first')\nFirst\n@endfragment\n@fragment('second')\nSecond\n@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertCount(4, $elements); // 2 opens + 2 closes
        $this->assertInstanceOf(OpenFragmentElement::class, $elements[0]);
        $this->assertInstanceOf(CloseFragmentElement::class, $elements[1]);
        $this->assertInstanceOf(OpenFragmentElement::class, $elements[2]);
        $this->assertInstanceOf(CloseFragmentElement::class, $elements[3]);
    }

    /** @test */
    public function test_parse_creates_open_elements()
    {
        $content = "@fragment('myFragment')\nContent\n@endfragment";

        $elements = $this->parser->parse($content);

        $openElement = $elements[0];
        $this->assertInstanceOf(OpenFragmentElement::class, $openElement);
        $this->assertEquals('myFragment', $openElement->name);
        $this->assertIsInt($openElement->startOffset);
        $this->assertIsInt($openElement->endOffset);
    }

    /** @test */
    public function test_parse_creates_close_elements()
    {
        $content = "@fragment('test')\nContent\n@endfragment";

        $elements = $this->parser->parse($content);

        $closeElement = $elements[1];
        $this->assertInstanceOf(CloseFragmentElement::class, $closeElement);
        $this->assertIsInt($closeElement->startOffset);
        $this->assertIsInt($closeElement->endOffset);
    }

    /** @test */
    public function test_parse_pairs_open_and_close_correctly()
    {
        $content = "@fragment('test')\n<p>Content</p>\n@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertCount(2, $elements);

        $openElement = $elements[0];
        $closeElement = $elements[1];

        // Close should come after open
        $this->assertGreaterThan($openElement->endOffset, $closeElement->startOffset);
    }

    // ==========================================
    // Edge Cases (6 methods)
    // ==========================================

    /** @test */
    public function test_parse_handles_empty_content()
    {
        $content = '';

        $elements = $this->parser->parse($content);

        $this->assertIsArray($elements);
        $this->assertEmpty($elements);
    }

    /** @test */
    public function test_parse_handles_content_without_fragments()
    {
        $content = '<div>Regular HTML content</div>';

        $elements = $this->parser->parse($content);

        $this->assertEmpty($elements);
    }

    /** @test */
    public function test_parse_handles_escaped_directives()
    {
        // @@fragment should be escaped and not parsed
        $content = "@@fragment('test')\nContent\n@@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertEmpty($elements);
    }

    /** @test */
    public function test_parse_preserves_line_numbers()
    {
        $content = "Line 1\nLine 2\n@fragment('test')\nLine 4\n@endfragment\nLine 6";

        $elements = $this->parser->parse($content);

        $this->assertCount(2, $elements);

        $openElement = $elements[0];
        // startOffset should point to beginning of @fragment
        $this->assertGreaterThan(0, $openElement->startOffset);
    }

    /** @test */
    public function test_parse_with_nested_structures()
    {
        $content = "@fragment('outer')\n@fragment('inner')\nInner\n@endfragment\nOuter\n@endfragment";

        $elements = $this->parser->parse($content);

        $this->assertCount(4, $elements); // 2 opens + 2 closes

        $outerOpen = $elements[0];
        $innerOpen = $elements[1];

        $this->assertEquals('outer', $outerOpen->name);
        $this->assertEquals('inner', $innerOpen->name);
    }

    /** @test */
    public function test_parse_handles_single_and_double_quotes()
    {
        $contentSingle = "@fragment('single')\nContent\n@endfragment";
        $contentDouble = '@fragment("double")\nContent\n@endfragment';

        $elementsSingle = $this->parser->parse($contentSingle);
        $elementsDouble = $this->parser->parse($contentDouble);

        $this->assertCount(2, $elementsSingle);
        $this->assertCount(2, $elementsDouble);

        $this->assertEquals('single', $elementsSingle[0]->name);
        $this->assertEquals('double', $elementsDouble[0]->name);
    }
}
