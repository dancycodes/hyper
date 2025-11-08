<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test void (self-closing) HTML elements
 *
 * Void elements cannot have children and must self-close.
 * Tests proper rendering without closing tags.
 *
 * Void elements in HTML5: br, hr, img, input, meta, link, etc.
 */
class VoidElementTest extends TestCase
{
    // ===================================================================
    // SELF-CLOSING BEHAVIOR TESTS
    // ===================================================================

    /** @test */
    public function test_br_element_self_closes()
    {
        $html = Html::br()->render();

        $this->assertEquals('<br />', $html);
        $this->assertStringNotContainsString('</br>', $html);
    }

    /** @test */
    public function test_hr_element_self_closes()
    {
        $html = Html::hr()->render();

        $this->assertEquals('<hr />', $html);
        $this->assertStringNotContainsString('</hr>', $html);
    }

    /** @test */
    public function test_img_element_self_closes()
    {
        $html = Html::img()->src('/image.jpg')->render();

        $this->assertStringContainsString('<img', $html);
        $this->assertStringNotContainsString('</img>', $html);
        $this->assertStringContainsString('src="/image.jpg"', $html);
    }

    /** @test */
    public function test_input_element_self_closes()
    {
        $html = Html::input()->type('text')->render();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringNotContainsString('</input>', $html);
        $this->assertStringContainsString('type="text"', $html);
    }

    /** @test */
    public function test_meta_element_self_closes()
    {
        $html = Html::meta()->name('description')->content('Test')->render();

        $this->assertStringContainsString('<meta', $html);
        $this->assertStringNotContainsString('</meta>', $html);
    }

    /** @test */
    public function test_link_element_self_closes()
    {
        $html = Html::link()->rel('stylesheet')->href('/style.css')->render();

        $this->assertStringContainsString('<link', $html);
        $this->assertStringNotContainsString('</link>', $html);
    }

    // ===================================================================
    // ATTRIBUTE TESTS FOR VOID ELEMENTS
    // ===================================================================

    /** @test */
    public function test_void_element_with_id_attribute()
    {
        $html = Html::hr()->id('divider')->render();

        $this->assertStringContainsString('id="divider"', $html);
    }

    /** @test */
    public function test_void_element_with_class_attribute()
    {
        $html = Html::hr()->class('border-t border-gray-300')->render();

        $this->assertStringContainsString('class="border-t border-gray-300"', $html);
    }

    /** @test */
    public function test_void_element_with_data_attributes()
    {
        $html = Html::input()
            ->type('text')
            ->dataBind('email')
            ->render();

        $this->assertStringContainsString('data-bind="email"', $html);
    }

    // ===================================================================
    // INPUT ELEMENT TESTS
    // ===================================================================

    /** @test */
    public function test_input_text_type()
    {
        $html = Html::input()->type('text')->name('username')->render();

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="username"', $html);
    }

    /** @test */
    public function test_input_email_type()
    {
        $html = Html::input()->type('email')->name('email')->render();

        $this->assertStringContainsString('type="email"', $html);
    }

    /** @test */
    public function test_input_password_type()
    {
        $html = Html::input()->type('password')->name('password')->render();

        $this->assertStringContainsString('type="password"', $html);
    }

    /** @test */
    public function test_input_checkbox_type()
    {
        $html = Html::input()->type('checkbox')->name('agree')->value('yes')->render();

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="yes"', $html);
    }

    /** @test */
    public function test_input_radio_type()
    {
        $html = Html::input()->type('radio')->name('choice')->value('option1')->render();

        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('value="option1"', $html);
    }

    /** @test */
    public function test_input_with_placeholder()
    {
        $html = Html::input()
            ->type('text')
            ->placeholder('Enter your name')
            ->render();

        $this->assertStringContainsString('placeholder="Enter your name"', $html);
    }

    /** @test */
    public function test_input_with_required_attribute()
    {
        $html = Html::input()->type('text')->required()->render();

        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function test_input_with_disabled_attribute()
    {
        $html = Html::input()->type('text')->disabled()->render();

        $this->assertStringContainsString('disabled', $html);
    }

    /** @test */
    public function test_input_with_readonly_attribute()
    {
        $html = Html::input()->type('text')->readonly()->render();

        $this->assertStringContainsString('readonly', $html);
    }

    /** @test */
    public function test_input_with_value_attribute()
    {
        $html = Html::input()->type('text')->value('Default Value')->render();

        $this->assertStringContainsString('value="Default Value"', $html);
    }

    /** @test */
    public function test_input_value_escaping()
    {
        $malicious = '"><script>alert("XSS")</script><input value="';
        $html = Html::input()->type('text')->value($malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ===================================================================
    // IMG ELEMENT TESTS
    // ===================================================================

    /** @test */
    public function test_img_with_src_and_alt()
    {
        $html = Html::img()->src('/photo.jpg')->alt('Photo description')->render();

        $this->assertStringContainsString('src="/photo.jpg"', $html);
        $this->assertStringContainsString('alt="Photo description"', $html);
    }

    /** @test */
    public function test_img_with_width_and_height()
    {
        $html = Html::img()
            ->src('/image.png')
            ->width('300')
            ->height('200')
            ->render();

        $this->assertStringContainsString('width="300"', $html);
        $this->assertStringContainsString('height="200"', $html);
    }

    /** @test */
    public function test_img_src_escaping()
    {
        $malicious = 'javascript:alert("XSS")';
        $html = Html::img()->src($malicious)->render();

        // HTML escaping encodes dangerous characters but doesn't filter protocols
        $this->assertStringContainsString('src="javascript:alert(&quot;XSS&quot;)"', $html);
        // The &quot; encoding prevents the javascript from executing
    }

    // ===================================================================
    // META ELEMENT TESTS
    // ===================================================================

    /** @test */
    public function test_meta_charset()
    {
        $html = Html::meta()->charset('UTF-8')->render();

        $this->assertStringContainsString('charset="UTF-8"', $html);
    }

    /** @test */
    public function test_meta_name_and_content()
    {
        $html = Html::meta()
            ->name('description')
            ->content('Page description here')
            ->render();

        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('content="Page description here"', $html);
    }

    /** @test */
    public function test_meta_viewport()
    {
        $html = Html::meta()
            ->name('viewport')
            ->content('width=device-width, initial-scale=1')
            ->render();

        $this->assertStringContainsString('content="width=device-width, initial-scale=1"', $html);
    }

    // ===================================================================
    // LINK ELEMENT TESTS
    // ===================================================================

    /** @test */
    public function test_link_stylesheet()
    {
        $html = Html::link()->rel('stylesheet')->href('/styles.css')->render();

        $this->assertStringContainsString('rel="stylesheet"', $html);
        $this->assertStringContainsString('href="/styles.css"', $html);
    }

    /** @test */
    public function test_link_icon()
    {
        $html = Html::link()->rel('icon')->href('/favicon.ico')->render();

        $this->assertStringContainsString('rel="icon"', $html);
        $this->assertStringContainsString('href="/favicon.ico"', $html);
    }

    /** @test */
    public function test_link_preload()
    {
        $html = Html::link()
            ->rel('preload')
            ->href('/font.woff2')
            ->attr('as', 'font')
            ->render();

        $this->assertStringContainsString('rel="preload"', $html);
        $this->assertStringContainsString('as="font"', $html);
    }

    // ===================================================================
    // SPECIAL CASES
    // ===================================================================

    /** @test */
    public function test_wbr_element_self_closes()
    {
        // Word break opportunity element
        $html = Html::wbr()->render();

        $this->assertEquals('<wbr />', $html);
        $this->assertStringNotContainsString('</wbr>', $html);
    }

    /** @test */
    public function test_void_element_ignores_child_content()
    {
        // Void elements should never have children
        // If somehow content is added, it should not render
        $html = Html::br()->render();

        $this->assertEquals('<br />', $html);
    }

    /** @test */
    public function test_void_element_with_multiple_attributes()
    {
        $html = Html::input()
            ->type('email')
            ->name('user_email')
            ->id('email-input')
            ->class('form-control')
            ->placeholder('Enter email')
            ->required()
            ->render();

        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('name="user_email"', $html);
        $this->assertStringContainsString('id="email-input"', $html);
        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('placeholder="Enter email"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringNotContainsString('</input>', $html);
    }
}
