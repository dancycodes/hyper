<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test ContainerElement security and real-world patterns
 *
 * Part 3 of 3 - XSS protection, edge cases, method chaining, and real-world patterns
 */
class ContainerElement_SecurityTest extends TestCase
{
    public static $latestResponse;

    // ===================================================================
    // XSS PROTECTION IN CHILDREN
    // ===================================================================

    /** @test */
    public function test_string_children_are_escaped()
    {
        $html = Html::div()->content(
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(1)">'
        )->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    /** @test */
    public function test_element_children_are_not_escaped()
    {
        $html = Html::div()->content(
            Html::strong('Bold text')
        )->render();

        // Element's HTML should NOT be escaped
        $this->assertStringContainsString('<strong>Bold text</strong>', $html);
    }

    /** @test */
    public function test_mixed_safe_and_unsafe_children()
    {
        $html = Html::div()->content(
            'Safe text',
            Html::strong('Bold'),
            '<script>alert("XSS")</script>'
        )->render();

        $this->assertStringContainsString('Safe text', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ===================================================================
    // EDGE CASES
    // ===================================================================

    /** @test */
    public function test_empty_container()
    {
        $html = Html::div()->render();

        $this->assertEquals('<div></div>', $html);
    }

    /** @test */
    public function test_container_with_only_text()
    {
        $html = Html::div()->text('Only text')->render();

        $this->assertEquals('<div>Only text</div>', $html);
    }

    /** @test */
    public function test_container_with_only_children()
    {
        $html = Html::div()->content(Html::p('Child'))->render();

        $this->assertStringContainsString('<p>Child</p>', $html);
    }

    /** @test */
    public function test_container_with_only_html()
    {
        $html = Html::div()->html('<strong>Only HTML</strong>')->render();

        $this->assertStringContainsString('<strong>Only HTML</strong>', $html);
    }

    /** @test */
    public function test_whitespace_in_children()
    {
        $html = Html::div()->content('  ', Html::p('Content'), '  ')->render();

        $this->assertStringContainsString('  <p>Content</p>  ', $html);
    }

    /** @test */
    public function test_numeric_string_children()
    {
        $html = Html::div()->content('0', '1', '2')->render();

        $this->assertEquals('<div>012</div>', $html);
    }

    /** @test */
    public function test_empty_string_children()
    {
        $html = Html::div()->content('', Html::p('Content'), '')->render();

        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    /** @test */
    public function test_array_with_mixed_nulls_and_content()
    {
        $html = Html::div()->content([
            null,
            Html::p('First'),
            null,
            Html::p('Second'),
            null,
        ])->render();

        $this->assertStringContainsString('<p>First</p>', $html);
        $this->assertStringContainsString('<p>Second</p>', $html);
    }

    /** @test */
    public function test_unicode_in_string_children()
    {
        $html = Html::div()->content('Hello 世界 🌍')->render();

        $this->assertStringContainsString('Hello 世界 🌍', $html);
    }

    /** @test */
    public function test_special_characters_in_string_children()
    {
        $html = Html::div()->content('Price: $100 & up')->render();

        $this->assertStringContainsString('$100 &amp; up', $html);
    }

    // ===================================================================
    // METHOD CHAINING WITH ATTRIBUTES
    // ===================================================================

    /** @test */
    public function test_content_with_attributes()
    {
        $html = Html::div()
            ->id('container')
            ->class('wrapper')
            ->content(Html::p('Content'))
            ->render();

        $this->assertStringContainsString('id="container"', $html);
        $this->assertStringContainsString('class="wrapper"', $html);
        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    /** @test */
    public function test_complex_chaining()
    {
        $html = Html::section()
            ->id('main')
            ->class('container')
            ->attr('data-section', 'content')
            ->text('Intro: ')
            ->content(
                Html::h1('Title'),
                Html::p('Paragraph')
            )
            ->html('<footer>Footer</footer>')
            ->render();

        $this->assertStringContainsString('id="main"', $html);
        $this->assertStringContainsString('class="container"', $html);
        $this->assertStringContainsString('data-section="content"', $html);
        $this->assertStringContainsString('Intro:', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<footer>Footer</footer>', $html);
    }

    // ===================================================================
    // REAL-WORLD PATTERNS
    // ===================================================================

    /** @test */
    public function test_card_component_pattern()
    {
        $html = Html::div()
            ->class('card')
            ->content(
                Html::div()->class('card-header')->content(
                    Html::h3('Card Title')
                ),
                Html::div()->class('card-body')->content(
                    Html::p('Card content goes here')
                ),
                Html::div()->class('card-footer')->content(
                    Html::button('Action')
                )
            )
            ->render();

        $this->assertStringContainsString('card-header', $html);
        $this->assertStringContainsString('<h3>Card Title</h3>', $html);
        $this->assertStringContainsString('card-body', $html);
        $this->assertStringContainsString('card-footer', $html);
    }

    /** @test */
    public function test_navigation_menu_pattern()
    {
        $html = Html::nav()->content(
            Html::ul()->content(
                Html::li()->content(Html::a()->href('/home')->text('Home')),
                Html::li()->content(Html::a()->href('/about')->text('About')),
                Html::li()->content(Html::a()->href('/contact')->text('Contact'))
            )
        )->render();

        $this->assertStringContainsString('<nav>', $html);
        $this->assertStringContainsString('href="/home"', $html);
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('Contact', $html);
    }

    /** @test */
    public function test_conditional_content_pattern()
    {
        $showExtra = true;

        $html = Html::div()->content(
            Html::h1('Title'),
            $showExtra ? Html::p('Extra content') : null,
            Html::footer()->text('Footer')
        )->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Extra content</p>', $html);
        $this->assertStringContainsString('<footer>Footer</footer>', $html);
    }
}
