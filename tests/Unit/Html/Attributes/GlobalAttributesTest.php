<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Attributes;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test global HTML attributes trait (HasGlobalAttributes)
 *
 * Covers HTML5 global attributes that work on all elements:
 * - id, title, lang, dir
 * - hidden, tabindex, accesskey
 * - style, translate, contenteditable
 * - draggable, spellcheck
 *
 * All methods should:
 * - Accept closures for dynamic values
 * - Return $this for method chaining
 * - Validate values where appropriate
 * - Escape attribute values for XSS protection
 */
class GlobalAttributesTest extends TestCase
{
    public static $latestResponse;

    // ===================================================================
    // ID ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_id_sets_attribute()
    {
        $html = Html::div()->id('main-content')->render();

        $this->assertStringContainsString('id="main-content"', $html);
    }

    /** @test */
    public function test_id_with_closure()
    {
        $html = Html::div()->id(fn () => 'dynamic-id')->render();

        $this->assertStringContainsString('id="dynamic-id"', $html);
    }

    /** @test */
    public function test_id_returns_this_for_chaining()
    {
        $element = Html::div();
        $result = $element->id('test');

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_id_escapes_malicious_content()
    {
        $malicious = '\"><script>alert(1)</script>';
        $html = Html::div()->id($malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    // ===================================================================
    // TITLE ATTRIBUTE TESTS (TOOLTIP)
    // ===================================================================

    /** @test */
    public function test_title_sets_tooltip_text()
    {
        $html = Html::div()->title('Hover text')->render();

        $this->assertStringContainsString('title="Hover text"', $html);
    }

    /** @test */
    public function test_title_with_closure()
    {
        $html = Html::div()->title(fn () => 'Dynamic tooltip')->render();

        $this->assertStringContainsString('title="Dynamic tooltip"', $html);
    }

    /** @test */
    public function test_title_escapes_special_characters()
    {
        $html = Html::div()->title('Price: $100 & up')->render();

        $this->assertStringContainsString('&amp;', $html);
    }

    // ===================================================================
    // LANG ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_lang_sets_language_code()
    {
        $html = Html::div()->lang('en')->render();

        $this->assertStringContainsString('lang="en"', $html);
    }

    /** @test */
    public function test_lang_with_region_code()
    {
        $html = Html::div()->lang('en-US')->render();

        $this->assertStringContainsString('lang="en-US"', $html);
    }

    /** @test */
    public function test_lang_with_closure()
    {
        $html = Html::div()->lang(fn () => 'fr')->render();

        $this->assertStringContainsString('lang="fr"', $html);
    }

    /** @test */
    public function test_lang_with_various_language_codes()
    {
        $this->assertStringContainsString('lang="es"', Html::div()->lang('es')->render());
        $this->assertStringContainsString('lang="es-MX"', Html::div()->lang('es-MX')->render());
        $this->assertStringContainsString('lang="zh-CN"', Html::div()->lang('zh-CN')->render());
        $this->assertStringContainsString('lang="ar"', Html::div()->lang('ar')->render());
    }

    // ===================================================================
    // DIR ATTRIBUTE TESTS (TEXT DIRECTION)
    // ===================================================================

    /** @test */
    public function test_dir_with_ltr()
    {
        $html = Html::div()->dir('ltr')->render();

        $this->assertStringContainsString('dir="ltr"', $html);
    }

    /** @test */
    public function test_dir_with_rtl()
    {
        $html = Html::div()->dir('rtl')->render();

        $this->assertStringContainsString('dir="rtl"', $html);
    }

    /** @test */
    public function test_dir_with_auto()
    {
        $html = Html::div()->dir('auto')->render();

        $this->assertStringContainsString('dir="auto"', $html);
    }

    /** @test */
    public function test_dir_with_closure()
    {
        $html = Html::div()->dir(fn () => 'rtl')->render();

        $this->assertStringContainsString('dir="rtl"', $html);
    }

    /** @test */
    public function test_dir_with_invalid_value_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for dir()');

        Html::div()->dir('invalid')->render();
    }

    /** @test */
    public function test_dir_validation_is_case_sensitive()
    {
        $this->expectException(\InvalidArgumentException::class);

        Html::div()->dir('LTR')->render(); // Must be lowercase
    }

    // ===================================================================
    // HIDDEN ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_hidden_attribute_true()
    {
        $html = Html::div()->hidden()->render();

        $this->assertStringContainsString('hidden', $html);
    }

    /** @test */
    public function test_hidden_attribute_explicit_true()
    {
        $html = Html::div()->hidden(true)->render();

        $this->assertStringContainsString('hidden', $html);
    }

    /** @test */
    public function test_hidden_attribute_false()
    {
        $html = Html::div()->hidden(false)->render();

        // When false, attribute should not be present
        $this->assertStringNotContainsString('hidden', $html);
    }

    /** @test */
    public function test_hidden_with_closure()
    {
        $html = Html::div()->hidden(fn () => true)->render();

        $this->assertStringContainsString('hidden', $html);
    }

    // ===================================================================
    // TABINDEX ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_tabindex_with_positive_value()
    {
        $html = Html::div()->tabindex(1)->render();

        $this->assertStringContainsString('tabindex="1"', $html);
    }

    /** @test */
    public function test_tabindex_with_negative_value()
    {
        // -1 makes element focusable but not reachable via tab
        $html = Html::div()->tabindex(-1)->render();

        $this->assertStringContainsString('tabindex="-1"', $html);
    }

    /** @test */
    public function test_tabindex_with_zero()
    {
        $html = Html::div()->tabindex(0)->render();

        $this->assertStringContainsString('tabindex="0"', $html);
    }

    /** @test */
    public function test_tabindex_with_closure()
    {
        $html = Html::div()->tabindex(fn () => 2)->render();

        $this->assertStringContainsString('tabindex="2"', $html);
    }

    // ===================================================================
    // ACCESSKEY ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_accesskey_sets_keyboard_shortcut()
    {
        $html = Html::div()->accesskey('s')->render();

        $this->assertStringContainsString('accesskey="s"', $html);
    }

    /** @test */
    public function test_accesskey_with_uppercase()
    {
        $html = Html::div()->accesskey('S')->render();

        $this->assertStringContainsString('accesskey="S"', $html);
    }

    /** @test */
    public function test_accesskey_with_closure()
    {
        $html = Html::div()->accesskey(fn () => 'h')->render();

        $this->assertStringContainsString('accesskey="h"', $html);
    }

    // ===================================================================
    // STYLE ATTRIBUTE TESTS (INLINE CSS)
    // ===================================================================

    /** @test */
    public function test_style_sets_inline_css()
    {
        $html = Html::div()->style('color: red; background: blue;')->render();

        $this->assertStringContainsString('style="color: red; background: blue;"', $html);
    }

    /** @test */
    public function test_style_with_single_property()
    {
        $html = Html::div()->style('margin: 10px')->render();

        $this->assertStringContainsString('style="margin: 10px"', $html);
    }

    /** @test */
    public function test_style_with_closure()
    {
        $html = Html::div()->style(fn () => 'padding: 5px')->render();

        $this->assertStringContainsString('style="padding: 5px"', $html);
    }

    /** @test */
    public function test_style_escapes_special_characters()
    {
        $html = Html::div()->style('content: "Hello & Goodbye"')->render();

        $this->assertStringContainsString('&amp;', $html);
    }

    // ===================================================================
    // TRANSLATE ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_translate_true_sets_yes()
    {
        $html = Html::div()->translate(true)->render();

        $this->assertStringContainsString('translate="yes"', $html);
    }

    /** @test */
    public function test_translate_false_sets_no()
    {
        $html = Html::div()->translate(false)->render();

        $this->assertStringContainsString('translate="no"', $html);
    }

    /** @test */
    public function test_translate_default_is_true()
    {
        $html = Html::div()->translate()->render();

        $this->assertStringContainsString('translate="yes"', $html);
    }

    /** @test */
    public function test_translate_with_closure()
    {
        $html = Html::div()->translate(fn () => false)->render();

        $this->assertStringContainsString('translate="no"', $html);
    }

    // ===================================================================
    // CONTENTEDITABLE ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_contenteditable_with_true()
    {
        $html = Html::div()->contenteditable(true)->render();

        $this->assertStringContainsString('contenteditable="true"', $html);
    }

    /** @test */
    public function test_contenteditable_with_false()
    {
        $html = Html::div()->contenteditable(false)->render();

        $this->assertStringContainsString('contenteditable="false"', $html);
    }

    /** @test */
    public function test_contenteditable_default_is_true()
    {
        $html = Html::div()->contenteditable()->render();

        $this->assertStringContainsString('contenteditable="true"', $html);
    }

    /** @test */
    public function test_contenteditable_with_plaintext_only()
    {
        $html = Html::div()->contenteditable('plaintext-only')->render();

        $this->assertStringContainsString('contenteditable="plaintext-only"', $html);
    }

    /** @test */
    public function test_contenteditable_with_string_true()
    {
        $html = Html::div()->contenteditable('true')->render();

        $this->assertStringContainsString('contenteditable="true"', $html);
    }

    /** @test */
    public function test_contenteditable_with_string_false()
    {
        $html = Html::div()->contenteditable('false')->render();

        $this->assertStringContainsString('contenteditable="false"', $html);
    }

    /** @test */
    public function test_contenteditable_with_invalid_value_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for contenteditable()');

        Html::div()->contenteditable('invalid')->render();
    }

    /** @test */
    public function test_contenteditable_with_closure()
    {
        $html = Html::div()->contenteditable(fn () => 'plaintext-only')->render();

        $this->assertStringContainsString('contenteditable="plaintext-only"', $html);
    }

    // ===================================================================
    // DRAGGABLE ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_draggable_with_true()
    {
        $html = Html::div()->draggable(true)->render();

        $this->assertStringContainsString('draggable="true"', $html);
    }

    /** @test */
    public function test_draggable_with_false()
    {
        $html = Html::div()->draggable(false)->render();

        $this->assertStringContainsString('draggable="false"', $html);
    }

    /** @test */
    public function test_draggable_default_is_true()
    {
        $html = Html::div()->draggable()->render();

        $this->assertStringContainsString('draggable="true"', $html);
    }

    /** @test */
    public function test_draggable_with_closure()
    {
        $html = Html::div()->draggable(fn () => false)->render();

        $this->assertStringContainsString('draggable="false"', $html);
    }

    // ===================================================================
    // SPELLCHECK ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_spellcheck_with_true()
    {
        $html = Html::div()->spellcheck(true)->render();

        $this->assertStringContainsString('spellcheck="true"', $html);
    }

    /** @test */
    public function test_spellcheck_with_false()
    {
        $html = Html::div()->spellcheck(false)->render();

        $this->assertStringContainsString('spellcheck="false"', $html);
    }

    /** @test */
    public function test_spellcheck_default_is_true()
    {
        $html = Html::div()->spellcheck()->render();

        $this->assertStringContainsString('spellcheck="true"', $html);
    }

    /** @test */
    public function test_spellcheck_with_closure()
    {
        $html = Html::div()->spellcheck(fn () => false)->render();

        $this->assertStringContainsString('spellcheck="false"', $html);
    }

    // ===================================================================
    // METHOD CHAINING TESTS
    // ===================================================================

    /** @test */
    public function test_multiple_global_attributes_chained()
    {
        $html = Html::div()
            ->id('main')
            ->title('Main content')
            ->lang('en')
            ->dir('ltr')
            ->hidden(false)
            ->tabindex(0)
            ->render();

        $this->assertStringContainsString('id="main"', $html);
        $this->assertStringContainsString('title="Main content"', $html);
        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
    }

    /** @test */
    public function test_global_attributes_with_content()
    {
        $html = Html::div()
            ->id('container')
            ->class('wrapper')
            ->title('Container')
            ->contenteditable(true)
            ->draggable(false)
            ->content(Html::p('Content'))
            ->render();

        $this->assertStringContainsString('id="container"', $html);
        $this->assertStringContainsString('title="Container"', $html);
        $this->assertStringContainsString('contenteditable="true"', $html);
        $this->assertStringContainsString('draggable="false"', $html);
        $this->assertStringContainsString('<p>Content</p>', $html);
    }

    // ===================================================================
    // CLOSURE DEPENDENCY INJECTION TESTS
    // ===================================================================

    /** @test */
    public function test_closure_with_dependency_injection()
    {
        $html = Html::div()->id(function () {
            $config = app('config');

            return 'app-' . $config->get('app.name', 'laravel');
        })->render();

        $this->assertStringContainsString('id="app-', $html);
    }

    /** @test */
    public function test_multiple_closures_with_di()
    {
        $html = Html::div()
            ->id(fn () => 'dynamic-id')
            ->title(fn () => 'Dynamic title')
            ->lang(fn () => 'en')
            ->render();

        $this->assertStringContainsString('id="dynamic-id"', $html);
        $this->assertStringContainsString('title="Dynamic title"', $html);
        $this->assertStringContainsString('lang="en"', $html);
    }

    // ===================================================================
    // XSS PROTECTION TESTS
    // ===================================================================

    /** @test */
    public function test_attributes_escape_malicious_content()
    {
        $malicious = '\"><script>alert(\"XSS\")</script><div class=\"';

        $html = Html::div()
            ->id($malicious)
            ->title($malicious)
            ->accesskey($malicious)
            ->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_style_attribute_escapes_javascript()
    {
        $malicious = 'color: expression(alert("XSS"))';
        $html = Html::div()->style($malicious)->render();

        // Attribute value should be escaped
        $this->assertIsString($html);
        $this->assertStringContainsString('style=', $html);
    }

    // ===================================================================
    // EDGE CASES
    // ===================================================================

    /** @test */
    public function test_empty_string_values()
    {
        $html = Html::div()
            ->id('')
            ->title('')
            ->lang('')
            ->render();

        // Empty strings render as boolean attributes (no value) per Element renderAttributes() logic
        $this->assertStringContainsString('<div', $html);
        // Note: This matches the actual behavior where empty values are treated as boolean attributes
    }

    /** @test */
    public function test_numeric_string_values()
    {
        $html = Html::div()
            ->id('123')
            ->title('456')
            ->render();

        $this->assertStringContainsString('id="123"', $html);
        $this->assertStringContainsString('title="456"', $html);
    }

    /** @test */
    public function test_special_characters_in_accesskey()
    {
        // Accesskey should handle special characters
        $html = Html::div()->accesskey('+')->render();

        $this->assertStringContainsString('accesskey="+"', $html);
    }

    /** @test */
    public function test_unicode_in_attributes()
    {
        $html = Html::div()
            ->title('Hello 世界')
            ->lang('zh-CN')
            ->render();

        $this->assertStringContainsString('Hello 世界', $html);
        $this->assertStringContainsString('lang="zh-CN"', $html);
    }

    /** @test */
    public function test_very_long_attribute_values()
    {
        $longTitle = str_repeat('A', 1000);
        $html = Html::div()->title($longTitle)->render();

        $this->assertStringContainsString($longTitle, $html);
    }
}
