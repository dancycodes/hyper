<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Concerns\Attributes\Form;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Html\Services\FormValidationRegistry;
use Dancycodes\Hyper\Tests\TestCase;

class HasValidationTest extends TestCase
{
    // ===================================================================
    // validate() Method Tests
    // ===================================================================

    /** @test */
    public function it_accepts_string_rules()
    {
        $input = Html::input()->name('email')->validate('required|email');

        $this->assertEquals(['email' => 'required|email'], $input->getValidationRules());
    }

    /** @test */
    public function it_accepts_array_rules()
    {
        $input = Html::input()->name('email')->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $rules = $input->getValidationRules();
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    /** @test */
    public function it_accepts_closure_rules()
    {
        $input = Html::input()->name('email')->validate(fn () => 'required|email');

        $this->assertEquals(['email' => 'required|email'], $input->getValidationRules());
    }

    /** @test */
    public function it_stores_custom_messages()
    {
        $input = Html::input()
            ->name('email')
            ->validate('required|email', ['email.required' => 'Email is mandatory']);

        $this->assertEquals(['email.required' => 'Email is mandatory'], $input->getValidationMessages());
    }

    /** @test */
    public function it_stores_custom_attributes()
    {
        $input = Html::input()
            ->name('user_email')
            ->validate('required', [], ['user_email' => 'email address']);

        $this->assertEquals(['user_email' => 'email address'], $input->getValidationAttributes());
    }

    /** @test */
    public function it_accepts_all_laravel_validate_parameters()
    {
        $input = Html::input()
            ->name('email')
            ->validate(
                'required|email',
                ['email.email' => 'Invalid email'],
                ['email' => 'email address']
            );

        $this->assertEquals(['email' => 'required|email'], $input->getValidationRules());
        $this->assertEquals(['email.email' => 'Invalid email'], $input->getValidationMessages());
        $this->assertEquals(['email' => 'email address'], $input->getValidationAttributes());
    }

    /** @test */
    public function it_returns_static_for_chaining()
    {
        $input = Html::input()->name('email');
        $result = $input->validate('required|email');

        $this->assertSame($input, $result);
    }

    // ===================================================================
    // clientSide Parameter Tests
    // ===================================================================

    /** @test */
    public function it_accepts_client_side_true()
    {
        $input = Html::input()->name('email')->validate('required|email', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('type="email"', $html);
    }

    /** @test */
    public function it_does_not_apply_html5_when_client_side_false()
    {
        $input = Html::input()->name('email')->validate('required|email', clientSide: false);

        $html = $input->render();

        // Should not have HTML5 attributes from validation
        $this->assertStringNotContainsString('required', $html);
    }

    /** @test */
    public function it_defaults_to_client_side_false()
    {
        $input = Html::input()->name('email')->validate('required|email');

        $html = $input->render();

        $this->assertStringNotContainsString('required', $html);
    }

    // ===================================================================
    // live Parameter Tests
    // ===================================================================

    /** @test */
    public function it_accepts_live_true()
    {
        $input = Html::input()->name('username')->validate('required|unique:users', live: true);

        $html = $input->render();

        $this->assertStringContainsString('data-on:', $html);
        $this->assertStringContainsString('debounce.300ms', $html);
        $this->assertStringContainsString('@patchx', $html);
        $this->assertStringContainsString('/validate/username', $html);
    }

    /** @test */
    public function it_does_not_apply_live_validation_when_live_false()
    {
        $input = Html::input()->name('username')->validate('required', live: false);

        $html = $input->render();

        $this->assertStringNotContainsString('debounce', $html);
    }

    /** @test */
    public function it_defaults_to_live_false()
    {
        $input = Html::input()->name('username')->validate('required');

        $html = $input->render();

        $this->assertStringNotContainsString('debounce', $html);
    }

    // ===================================================================
    // withError() Method Tests
    // ===================================================================

    /** @test */
    public function it_generates_error_div()
    {
        $input = Html::input()->name('email')->validate('required')->withError();

        $html = $input->render();

        $this->assertStringContainsString('data-error="email"', $html);
    }

    /** @test */
    public function it_uses_custom_error_class()
    {
        $input = Html::input()
            ->name('email')
            ->validate('required')
            ->withError('text-red-600 font-bold');

        $html = $input->render();

        $this->assertStringContainsString('text-red-600 font-bold', $html);
    }

    /** @test */
    public function it_uses_default_error_class()
    {
        $input = Html::input()->name('email')->validate('required')->withError();

        $html = $input->render();

        $this->assertStringContainsString('text-red-500 text-sm mt-1', $html);
    }

    /** @test */
    public function it_returns_static_for_chaining_with_error()
    {
        $input = Html::input()->name('email');
        $result = $input->withError();

        $this->assertSame($input, $result);
    }

    /** @test */
    public function it_accepts_closure_for_class()
    {
        $input = Html::input()
            ->name('email')
            ->validate('required')
            ->withError(fn () => 'text-red-600');

        $html = $input->render();

        $this->assertStringContainsString('text-red-600', $html);
    }

    // ===================================================================
    // getValidationData() Method Tests
    // ===================================================================

    /** @test */
    public function it_returns_complete_validation_data()
    {
        $input = Html::input()
            ->name('email')
            ->validate(
                'required|email',
                ['email.required' => 'Required'],
                ['email' => 'email address']
            );

        $data = $input->getValidationData();

        $this->assertArrayHasKey('rules', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertArrayHasKey('attributes', $data);
        $this->assertEquals(['email' => 'required|email'], $data['rules']);
        $this->assertEquals(['email.required' => 'Required'], $data['messages']);
        $this->assertEquals(['email' => 'email address'], $data['attributes']);
    }

    /** @test */
    public function it_returns_empty_arrays_for_unset_data()
    {
        $input = Html::input()->name('email');

        $data = $input->getValidationData();

        $this->assertEquals([
            'rules' => [],
            'messages' => [],
            'attributes' => [],
        ], $data);
    }

    // ===================================================================
    // collectValidationData() Method Tests
    // ===================================================================

    /** @test */
    public function it_collects_data_from_single_input()
    {
        $input = Html::input()->name('email')->validate('required|email');

        $data = $input->collectValidationData();

        $this->assertEquals(['email' => 'required|email'], $data['rules']);
    }

    /** @test */
    public function it_collects_data_from_container_with_children()
    {
        $form = Html::form()->content(
            Html::input()->name('email')->validate('required|email'),
            Html::input()->name('password')->validate('required|min:8')
        );

        $data = $form->collectValidationData();

        $this->assertArrayHasKey('email', $data['rules']);
        $this->assertArrayHasKey('password', $data['rules']);
        $this->assertEquals('required|email', $data['rules']['email']);
        $this->assertEquals('required|min:8', $data['rules']['password']);
    }

    /** @test */
    public function it_collects_deeply_nested_validation_data()
    {
        $form = Html::form()->content(
            Html::div()->content(
                Html::input()->name('email')->validate('required|email')
            ),
            Html::div()->content(
                Html::div()->content(
                    Html::input()->name('password')->validate('required|min:8')
                )
            )
        );

        $data = $form->collectValidationData();

        $this->assertArrayHasKey('email', $data['rules']);
        $this->assertArrayHasKey('password', $data['rules']);
    }

    // ===================================================================
    // HTML5 Attribute Generation Tests
    // ===================================================================

    /** @test */
    public function it_generates_required_attribute()
    {
        $input = Html::input()->name('email')->validate('required', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function it_generates_type_email_attribute()
    {
        $input = Html::input()->name('email')->validate('email', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('type="email"', $html);
    }

    /** @test */
    public function it_generates_minlength_attribute()
    {
        $input = Html::input()->name('password')->validate('min:8', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('minlength="8"', $html);
    }

    /** @test */
    public function it_generates_maxlength_attribute()
    {
        $input = Html::input()->name('name')->validate('max:255', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('maxlength="255"', $html);
    }

    /** @test */
    public function it_combines_multiple_html5_attributes()
    {
        $input = Html::input()
            ->name('email')
            ->validate('required|email|max:255', clientSide: true);

        $html = $input->render();

        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('maxlength="255"', $html);
    }

    // ===================================================================
    // Live Validation Registration Tests
    // ===================================================================

    /** @test */
    public function it_registers_field_with_form_validation_registry()
    {
        // Clear registry before test
        app(FormValidationRegistry::class)->clear();

        $input = Html::input()
            ->name('username')
            ->validate('required|unique:users', live: true);

        // Trigger rendering to apply live validation
        $input->render();

        $registry = app(FormValidationRegistry::class);

        $this->assertEquals('required|unique:users', $registry->getRulesForField('username'));
    }

    /** @test */
    public function it_attaches_debounced_data_on_action()
    {
        $input = Html::input()
            ->name('username')
            ->validate('required', live: true);

        $html = $input->render();

        $this->assertStringContainsString('data-on:input__debounce.300ms', $html);
        $this->assertStringContainsString('@patchx(\'/validate/username\')', $html);
    }

    /** @test */
    public function it_uses_smart_event_detection_for_different_input_types()
    {
        // Text input uses 'input' event
        $textInput = Html::input()
            ->name('username')
            ->type('text')
            ->validate('required', live: true);

        $textHtml = $textInput->render();
        $this->assertStringContainsString('data-on:input__debounce.300ms', $textHtml);

        // Checkbox uses 'change' event
        $checkbox = Html::input()
            ->name('agree')
            ->type('checkbox')
            ->validate('required', live: true);

        $checkboxHtml = $checkbox->render();
        $this->assertStringContainsString('data-on:change__debounce.300ms', $checkboxHtml);
    }

    // ===================================================================
    // Complete Integration Tests
    // ===================================================================

    /** @test */
    public function it_works_with_all_parameters_combined()
    {
        $input = Html::input()
            ->name('email')
            ->validate(
                'required|email|max:255',
                ['email.email' => 'Invalid format'],
                ['email' => 'email address'],
                clientSide: true,
                live: true
            )
            ->withError('text-red-600');

        $html = $input->render();

        // HTML5 attributes
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('maxlength="255"', $html);

        // Live validation
        $this->assertStringContainsString('data-on:input__debounce.300ms', $html);
        $this->assertStringContainsString('@patchx(\'/validate/email\')', $html);

        // Error div
        $this->assertStringContainsString('data-error="email"', $html);
        $this->assertStringContainsString('text-red-600', $html);
    }
}
