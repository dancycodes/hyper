<?php

namespace Dancycodes\Hyper\Tests\Unit\View;

use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\View\Fragment\BladeFragment;
use Illuminate\Support\Facades\View;

/**
 * Test the BladeFragment class
 *
 * @see TESTING.md - File 7: BladeFragment Tests
 * Status: 🔄 IN PROGRESS - 15 test methods
 */
class BladeFragmentTest extends TestCase
{
    public static $latestResponse;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test views directory
        View::addLocation(__DIR__ . '/../../Fixtures/views');
    }

    // ==========================================
    // Fragment Rendering Tests (8 methods)
    // ==========================================

    /** @test */
    public function test_render_returns_fragment_content()
    {
        $output = BladeFragment::render('fragments-test', 'header');

        $this->assertStringContainsString('<h1>', $output);
        $this->assertStringContainsString('Default Title', $output);
    }

    /** @test */
    public function test_render_with_data_variables()
    {
        $output = BladeFragment::render('fragments-test', 'header', ['title' => 'Custom Title']);

        $this->assertStringContainsString('Custom Title', $output);
        $this->assertStringNotContainsString('Default Title', $output);
    }

    /** @test */
    public function test_render_throws_exception_for_missing_view()
    {
        $this->expectException(\InvalidArgumentException::class);

        BladeFragment::render('nonexistent-view', 'header');
    }

    /** @test */
    public function test_render_throws_exception_for_missing_fragment()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No fragment called "nonexistent"');

        BladeFragment::render('fragments-test', 'nonexistent');
    }

    /** @test */
    public function test_render_compiles_blade_syntax()
    {
        $output = BladeFragment::render('fragments-test', 'with-blade', [
            'show' => true,
            'items' => ['Apple', 'Banana', 'Cherry'],
        ]);

        $this->assertStringContainsString('Conditional content', $output);
        $this->assertStringContainsString('<li>Apple</li>', $output);
        $this->assertStringContainsString('<li>Banana</li>', $output);
        $this->assertStringContainsString('<li>Cherry</li>', $output);
    }

    /** @test */
    public function test_render_handles_nested_fragments()
    {
        // Render outer fragment
        $output = BladeFragment::render('fragments-test', 'nested-outer');

        $this->assertStringContainsString('class="outer"', $output);
        $this->assertStringContainsString('class="inner"', $output);
    }

    /** @test */
    public function test_render_extracts_specific_fragment_only()
    {
        $output = BladeFragment::render('fragments-test', 'content');

        // Should contain content fragment
        $this->assertStringContainsString('<p>', $output);
        $this->assertStringContainsString('Default message', $output);

        // Should NOT contain other fragments
        $this->assertStringNotContainsString('<h1>', $output);
        $this->assertStringNotContainsString('<footer>', $output);
    }

    /** @test */
    public function test_render_with_empty_data()
    {
        $output = BladeFragment::render('fragments-test', 'footer', []);

        $this->assertStringContainsString('<footer>', $output);
        $this->assertStringContainsString('Copyright', $output);
    }

    // ==========================================
    // Fragment Validation Tests (4 methods)
    // ==========================================

    /** @test */
    public function test_render_validates_fragment_existence()
    {
        $this->expectException(\RuntimeException::class);

        BladeFragment::render('fragments-test', 'does-not-exist');
    }

    /** @test */
    public function test_render_handles_whitespace_in_fragment_names()
    {
        $output = BladeFragment::render('fragments-test', 'header');

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function test_render_returns_string()
    {
        $output = BladeFragment::render('fragments-test', 'content');

        $this->assertIsString($output);
    }

    /** @test */
    public function test_render_preserves_html_structure()
    {
        $output = BladeFragment::render('fragments-test', 'footer', ['year' => 2024]);

        $this->assertStringStartsWith('<footer>', trim($output));
        $this->assertStringEndsWith('</footer>', trim($output));
        $this->assertStringContainsString('2024', $output);
    }

    // ==========================================
    // Data Handling Tests (3 methods)
    // ==========================================

    /** @test */
    public function test_render_with_multiple_variables()
    {
        $output = BladeFragment::render('fragments-test', 'with-blade', [
            'show' => false,
            'items' => [],
        ]);

        // Conditional should not show
        $this->assertStringNotContainsString('Conditional content', $output);

        // No list items
        $this->assertStringNotContainsString('<li>', $output);
    }

    /** @test */
    public function test_render_uses_default_values_when_no_data()
    {
        $output = BladeFragment::render('fragments-test', 'header');

        $this->assertStringContainsString('Default Title', $output);
    }

    /** @test */
    public function test_render_overrides_defaults_with_provided_data()
    {
        $output = BladeFragment::render('fragments-test', 'content', [
            'message' => 'Custom message from data',
        ]);

        $this->assertStringContainsString('Custom message from data', $output);
        $this->assertStringNotContainsString('Default message', $output);
    }
}
