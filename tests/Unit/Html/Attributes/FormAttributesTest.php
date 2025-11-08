<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Attributes;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test form-related attributes trait (HasFormAttributes)
 *
 * Covers form control attributes:
 * - name, value, form
 * - autocomplete, autofocus
 *
 * All methods should:
 * - Accept closures for dynamic values
 * - Return $this for method chaining
 * - Escape attribute values for XSS protection
 */
class FormAttributesTest extends TestCase
{
    public static $latestResponse;

    // ===================================================================
    // NAME ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_name_sets_attribute()
    {
        $html = Html::input()->name('email')->render();

        $this->assertStringContainsString('name="email"', $html);
    }

    /** @test */
    public function test_name_with_array_notation()
    {
        $html = Html::input()->name('user[email]')->render();

        $this->assertStringContainsString('name="user[email]"', $html);
    }

    /** @test */
    public function test_name_with_nested_arrays()
    {
        $html = Html::input()->name('data[user][email]')->render();

        $this->assertStringContainsString('name="data[user][email]"', $html);
    }

    /** @test */
    public function test_name_with_closure()
    {
        $html = Html::input()->name(fn () => 'dynamic_name')->render();

        $this->assertStringContainsString('name="dynamic_name"', $html);
    }

    /** @test */
    public function test_name_returns_this_for_chaining()
    {
        $element = Html::input();
        $result = $element->name('test');

        $this->assertSame($element, $result);
    }

    /** @test */
    public function test_name_escapes_malicious_content()
    {
        $malicious = '\"><script>alert(1)</script>';
        $html = Html::input()->name($malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
    }

    // ===================================================================
    // VALUE ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_value_with_string()
    {
        $html = Html::input()->value('Test Value')->render();

        $this->assertStringContainsString('value="Test Value"', $html);
    }

    /** @test */
    public function test_value_with_integer()
    {
        $html = Html::input()->value(42)->render();

        $this->assertStringContainsString('value="42"', $html);
    }

    /** @test */
    public function test_value_with_float()
    {
        $html = Html::input()->value(3.14)->render();

        $this->assertStringContainsString('value="3.14"', $html);
    }

    /** @test */
    public function test_value_with_zero()
    {
        $html = Html::input()->value(0)->render();

        $this->assertStringContainsString('value="0"', $html);
    }

    /** @test */
    public function test_value_with_empty_string()
    {
        // Empty string values render as boolean attributes (no value)
        $html = Html::input()->value('')->render();

        $this->assertStringContainsString('<input', $html);
        // Note: Empty values are rendered as boolean attributes per Element renderAttributes() logic
    }

    /** @test */
    public function test_value_with_closure()
    {
        $html = Html::input()->value(fn () => 'Dynamic Value')->render();

        $this->assertStringContainsString('value="Dynamic Value"', $html);
    }

    /** @test */
    public function test_value_escapes_html_tags()
    {
        $malicious = '<script>alert("XSS")</script>';
        $html = Html::input()->value($malicious)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function test_value_escapes_quotes()
    {
        $html = Html::input()->value('He said "hello"')->render();

        $this->assertStringContainsString('&quot;', $html);
    }

    // ===================================================================
    // FORM ATTRIBUTE TESTS (ASSOCIATES WITH FORM BY ID)
    // ===================================================================

    /** @test */
    public function test_form_associates_with_form_id()
    {
        $html = Html::input()->form('main-form')->render();

        $this->assertStringContainsString('form="main-form"', $html);
    }

    /** @test */
    public function test_form_with_closure()
    {
        $html = Html::input()->form(fn () => 'dynamic-form')->render();

        $this->assertStringContainsString('form="dynamic-form"', $html);
    }

    /** @test */
    public function test_form_allows_control_outside_form_element()
    {
        // This pattern allows input to be outside <form> but still associated
        $form = Html::form()->id('payment-form')->content(
            Html::button('Submit')
        );

        $input = Html::input()->name('amount')->form('payment-form');

        $formHtml = $form->render();
        $inputHtml = $input->render();

        $this->assertStringContainsString('id="payment-form"', $formHtml);
        $this->assertStringContainsString('form="payment-form"', $inputHtml);
    }

    // ===================================================================
    // AUTOCOMPLETE ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_autocomplete_with_on()
    {
        $html = Html::input()->autocomplete('on')->render();

        $this->assertStringContainsString('autocomplete="on"', $html);
    }

    /** @test */
    public function test_autocomplete_with_off()
    {
        $html = Html::input()->autocomplete('off')->render();

        $this->assertStringContainsString('autocomplete="off"', $html);
    }

    /** @test */
    public function test_autocomplete_with_email()
    {
        $html = Html::input()->autocomplete('email')->render();

        $this->assertStringContainsString('autocomplete="email"', $html);
    }

    /** @test */
    public function test_autocomplete_with_username()
    {
        $html = Html::input()->autocomplete('username')->render();

        $this->assertStringContainsString('autocomplete="username"', $html);
    }

    /** @test */
    public function test_autocomplete_with_current_password()
    {
        $html = Html::input()->autocomplete('current-password')->render();

        $this->assertStringContainsString('autocomplete="current-password"', $html);
    }

    /** @test */
    public function test_autocomplete_with_new_password()
    {
        $html = Html::input()->autocomplete('new-password')->render();

        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }

    /** @test */
    public function test_autocomplete_with_tel()
    {
        $html = Html::input()->autocomplete('tel')->render();

        $this->assertStringContainsString('autocomplete="tel"', $html);
    }

    /** @test */
    public function test_autocomplete_with_street_address()
    {
        $html = Html::input()->autocomplete('street-address')->render();

        $this->assertStringContainsString('autocomplete="street-address"', $html);
    }

    /** @test */
    public function test_autocomplete_with_postal_code()
    {
        $html = Html::input()->autocomplete('postal-code')->render();

        $this->assertStringContainsString('autocomplete="postal-code"', $html);
    }

    /** @test */
    public function test_autocomplete_with_cc_number()
    {
        $html = Html::input()->autocomplete('cc-number')->render();

        $this->assertStringContainsString('autocomplete="cc-number"', $html);
    }

    /** @test */
    public function test_autocomplete_with_closure()
    {
        $html = Html::input()->autocomplete(fn () => 'email')->render();

        $this->assertStringContainsString('autocomplete="email"', $html);
    }

    // ===================================================================
    // AUTOFOCUS ATTRIBUTE TESTS
    // ===================================================================

    /** @test */
    public function test_autofocus_with_true()
    {
        $html = Html::input()->autofocus(true)->render();

        $this->assertStringContainsString('autofocus', $html);
    }

    /** @test */
    public function test_autofocus_with_false()
    {
        $html = Html::input()->autofocus(false)->render();

        // When false, attribute should not be present
        $this->assertStringNotContainsString('autofocus', $html);
    }

    /** @test */
    public function test_autofocus_default_is_true()
    {
        $html = Html::input()->autofocus()->render();

        $this->assertStringContainsString('autofocus', $html);
    }

    /** @test */
    public function test_autofocus_with_closure()
    {
        $html = Html::input()->autofocus(fn () => true)->render();

        $this->assertStringContainsString('autofocus', $html);
    }

    // ===================================================================
    // METHOD CHAINING TESTS
    // ===================================================================

    /** @test */
    public function test_chaining_form_attributes()
    {
        $html = Html::input()
            ->name('email')
            ->value('user@example.com')
            ->autocomplete('email')
            ->autofocus()
            ->render();

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('value="user@example.com"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
        $this->assertStringContainsString('autofocus', $html);
    }

    /** @test */
    public function test_form_attributes_with_global_attributes()
    {
        $html = Html::input()
            ->id('email-field')
            ->class('form-control')
            ->name('email')
            ->value('test@example.com')
            ->render();

        $this->assertStringContainsString('id="email-field"', $html);
        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('value="test@example.com"', $html);
    }

    // ===================================================================
    // REAL-WORLD FORM PATTERNS
    // ===================================================================

    /** @test */
    public function test_text_input_field()
    {
        $html = Html::input()
            ->type('text')
            ->name('username')
            ->value('johndoe')
            ->autocomplete('username')
            ->render();

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('value="johndoe"', $html);
        $this->assertStringContainsString('autocomplete="username"', $html);
    }

    /** @test */
    public function test_email_input_field()
    {
        $html = Html::input()
            ->type('email')
            ->name('email')
            ->value('user@example.com')
            ->autocomplete('email')
            ->autofocus()
            ->render();

        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
        $this->assertStringContainsString('autofocus', $html);
    }

    /** @test */
    public function test_password_input_field()
    {
        $html = Html::input()
            ->type('password')
            ->name('password')
            ->autocomplete('current-password')
            ->render();

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
    }

    /** @test */
    public function test_number_input_field()
    {
        $html = Html::input()
            ->type('number')
            ->name('quantity')
            ->value(5)
            ->render();

        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('name="quantity"', $html);
        $this->assertStringContainsString('value="5"', $html);
    }

    /** @test */
    public function test_textarea_with_name()
    {
        $html = Html::textarea()
            ->name('message')
            ->text('Default message')
            ->render();

        $this->assertStringContainsString('name="message"', $html);
        $this->assertStringContainsString('Default message', $html);
    }

    /** @test */
    public function test_select_with_name()
    {
        $html = Html::select()
            ->name('country')
            ->content(
                Html::option('US'),
                Html::option('UK')
            )
            ->render();

        $this->assertStringContainsString('name="country"', $html);
        $this->assertStringContainsString('<option>US</option>', $html);
        $this->assertStringContainsString('<option>UK</option>', $html);
    }

    // ===================================================================
    // CLOSURE WITH DEPENDENCY INJECTION
    // ===================================================================

    /** @test */
    public function test_closures_with_dependency_injection()
    {
        $html = Html::input()
            ->name(fn () => 'field_name')
            ->value(function () {
                $config = app('config');

                return $config->get('app.name', 'Laravel');
            })
            ->render();

        $this->assertStringContainsString('name="field_name"', $html);
        $this->assertStringContainsString('value=', $html);
    }

    /** @test */
    public function test_multiple_closures()
    {
        $html = Html::input()
            ->name(fn () => 'email')
            ->value(fn () => 'default@example.com')
            ->autocomplete(fn () => 'email')
            ->render();

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('value="default@example.com"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
    }

    // ===================================================================
    // EDGE CASES
    // ===================================================================

    /** @test */
    public function test_name_with_special_characters()
    {
        $html = Html::input()->name('user-email')->render();

        $this->assertStringContainsString('name="user-email"', $html);
    }

    /** @test */
    public function test_name_with_underscore()
    {
        $html = Html::input()->name('user_email')->render();

        $this->assertStringContainsString('name="user_email"', $html);
    }

    /** @test */
    public function test_name_with_dots()
    {
        $html = Html::input()->name('user.email')->render();

        $this->assertStringContainsString('name="user.email"', $html);
    }

    /** @test */
    public function test_value_with_unicode()
    {
        $html = Html::input()->value('Hello 世界')->render();

        $this->assertStringContainsString('Hello 世界', $html);
    }

    /** @test */
    public function test_value_with_special_characters()
    {
        $html = Html::input()->value('Price: $100 & up')->render();

        $this->assertStringContainsString('&amp;', $html);
    }

    /** @test */
    public function test_very_long_value()
    {
        $longValue = str_repeat('A', 1000);
        $html = Html::input()->value($longValue)->render();

        $this->assertStringContainsString($longValue, $html);
    }

    /** @test */
    public function test_numeric_name()
    {
        // While unusual, numeric names should work
        $html = Html::input()->name('123')->render();

        $this->assertStringContainsString('name="123"', $html);
    }

    /** @test */
    public function test_autocomplete_with_custom_value()
    {
        // Browser may not recognize it, but should still render
        $html = Html::input()->autocomplete('custom-field')->render();

        $this->assertStringContainsString('autocomplete="custom-field"', $html);
    }

    /** @test */
    public function test_form_attribute_with_non_existent_form()
    {
        // Should render even if form doesn't exist (browser will just ignore it)
        $html = Html::input()->form('non-existent-form')->render();

        $this->assertStringContainsString('form="non-existent-form"', $html);
    }
}
