<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Concerns\Form;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;

class ManagesValidationTest extends TestCase
{
    // ===================================================================
    // withSignals() Method Tests
    // ===================================================================

    /** @test */
    public function it_auto_generates_signals_attribute()
    {
        $form = Html::form()
            ->withSignals()
            ->content(
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('data-signals', $html);
        $this->assertStringContainsString('email', $html);
        $this->assertStringContainsString('password', $html);
        $this->assertStringContainsString('errors', $html);
    }

    /** @test */
    public function it_initializes_fields_as_empty_strings()
    {
        $form = Html::form()
            ->withSignals()
            ->content(
                Html::input()->name('email')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('"email":""', $html);
    }

    /** @test */
    public function it_initializes_errors_as_empty_array()
    {
        $form = Html::form()
            ->withSignals()
            ->content(
                Html::input()->name('email')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('"errors":[]', $html);
    }

    /** @test */
    public function it_returns_static_for_chaining()
    {
        $form = Html::form();
        $result = $form->withSignals();

        $this->assertSame($form, $result);
    }

    // ===================================================================
    // withErrors() Method Tests
    // ===================================================================

    /** @test */
    public function it_auto_generates_error_divs_for_validated_inputs()
    {
        $form = Html::form()
            ->withErrors()
            ->content(
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('data-error="email"', $html);
        $this->assertStringContainsString('data-error="password"', $html);
    }

    /** @test */
    public function it_uses_custom_error_class()
    {
        $form = Html::form()
            ->withErrors('text-red-600 font-bold')
            ->content(
                Html::input()->name('email')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('text-red-600 font-bold', $html);
    }

    /** @test */
    public function it_uses_default_error_class()
    {
        $form = Html::form()
            ->withErrors()
            ->content(
                Html::input()->name('email')->validate('required')
            );

        $html = $form->render();

        $this->assertStringContainsString('text-red-500 text-sm mt-1', $html);
    }

    /** @test */
    public function it_does_not_generate_error_divs_for_non_validated_inputs()
    {
        $form = Html::form()
            ->withErrors()
            ->content(
                Html::input()->name('email')->validate('required'),
                Html::input()->name('non_validated')  // No validation
            );

        $html = $form->render();

        $this->assertStringContainsString('data-error="email"', $html);
        $this->assertStringNotContainsString('data-error="non_validated"', $html);
    }

    /** @test */
    public function it_returns_static_for_chaining_with_errors()
    {
        $form = Html::form();
        $result = $form->withErrors();

        $this->assertSame($form, $result);
    }

    // ===================================================================
    // validationGroup() Method Tests
    // ===================================================================

    /** @test */
    public function it_creates_validation_group()
    {
        $form = Html::form()
            ->validationGroup('step1', ['name', 'email'])
            ->content(
                Html::input()->name('name')->validate('required'),
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required')
            );

        $rules = $form->getValidationRules('step1');

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayNotHasKey('password', $rules);
    }

    /** @test */
    public function it_creates_multiple_validation_groups()
    {
        $form = Html::form()
            ->validationGroup('personal', ['name', 'email'])
            ->validationGroup('account', ['password', 'password_confirmation'])
            ->content(
                Html::input()->name('name')->validate('required'),
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required|min:8'),
                Html::input()->name('password_confirmation')->validate('required')
            );

        $personalRules = $form->getValidationRules('personal');
        $accountRules = $form->getValidationRules('account');

        $this->assertArrayHasKey('name', $personalRules);
        $this->assertArrayHasKey('email', $personalRules);
        $this->assertArrayHasKey('password', $accountRules);
        $this->assertArrayHasKey('password_confirmation', $accountRules);
    }

    /** @test */
    public function it_returns_static_for_chaining_validation_group()
    {
        $form = Html::form();
        $result = $form->validationGroup('step1', ['name']);

        $this->assertSame($form, $result);
    }

    // ===================================================================
    // getValidationRules() Method Tests
    // ===================================================================

    /** @test */
    public function it_returns_all_rules_when_no_group_specified()
    {
        $form = Html::form()->content(
            Html::input()->name('email')->validate('required|email'),
            Html::input()->name('password')->validate('required')
        );

        $rules = $form->getValidationRules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertEquals('required|email', $rules['email']);
        $this->assertEquals('required', $rules['password']);
    }

    /** @test */
    public function it_returns_group_rules_when_group_specified()
    {
        $form = Html::form()
            ->validationGroup('step1', ['email'])
            ->content(
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required')
            );

        $rules = $form->getValidationRules('step1');

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayNotHasKey('password', $rules);
    }

    /** @test */
    public function it_collects_rules_from_nested_elements()
    {
        $form = Html::form()->content(
            Html::div()->content(
                Html::input()->name('email')->validate('required|email')
            ),
            Html::div()->content(
                Html::div()->content(
                    Html::input()->name('password')->validate('required')
                )
            )
        );

        $rules = $form->getValidationRules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_group()
    {
        $form = Html::form()->content(
            Html::input()->name('email')->validate('required')
        );

        $rules = $form->getValidationRules('nonexistent');

        $this->assertEquals([], $rules);
    }

    // ===================================================================
    // Complete Integration Tests
    // ===================================================================

    /** @test */
    public function it_combines_with_signals_and_with_errors()
    {
        $form = Html::form()
            ->withSignals()
            ->withErrors()
            ->postx('/submit')
            ->content(
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required|min:8')
            );

        $html = $form->render();

        // Has signals
        $this->assertStringContainsString('data-signals', $html);
        $this->assertStringContainsString('"email":""', $html);
        $this->assertStringContainsString('"password":""', $html);
        $this->assertStringContainsString('"errors":[]', $html);

        // Has error divs
        $this->assertStringContainsString('data-error="email"', $html);
        $this->assertStringContainsString('data-error="password"', $html);

        // Has action
        $this->assertStringContainsString('data-on:submit__prevent', $html);
        $this->assertStringContainsString('@postx(\'/submit\')', $html);
    }

    /** @test */
    public function it_works_with_validation_groups()
    {
        $form = Html::form()
            ->withSignals()
            ->withErrors()
            ->validationGroup('step1', ['name', 'email'])
            ->validationGroup('step2', ['password'])
            ->content(
                Html::input()->name('name')->validate('required'),
                Html::input()->name('email')->validate('required|email'),
                Html::input()->name('password')->validate('required|min:8')
            );

        $step1Rules = $form->getValidationRules('step1');
        $step2Rules = $form->getValidationRules('step2');

        $this->assertArrayHasKey('name', $step1Rules);
        $this->assertArrayHasKey('email', $step1Rules);
        $this->assertArrayHasKey('password', $step2Rules);
        $this->assertArrayNotHasKey('email', $step2Rules);
    }

    /** @test */
    public function it_works_with_client_side_and_live_parameters()
    {
        $form = Html::form()
            ->withSignals()
            ->withErrors()
            ->content(
                Html::input()
                    ->name('username')
                    ->validate('required|unique:users', clientSide: true, live: true),
                Html::input()
                    ->name('email')
                    ->validate('required|email', clientSide: true)
            );

        $html = $form->render();

        // HTML5 attributes
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('type="email"', $html);

        // Live validation
        $this->assertStringContainsString('data-on:input__debounce.300ms', $html);
        $this->assertStringContainsString('@patchx(\'/validate/username\')', $html);

        // Error divs
        $this->assertStringContainsString('data-error="username"', $html);
        $this->assertStringContainsString('data-error="email"', $html);
    }

    // ===================================================================
    // Real-World Scenarios Tests
    // ===================================================================

    /** @test */
    public function it_handles_registration_form()
    {
        $form = Html::form()
            ->withSignals()
            ->withErrors('text-red-600 text-xs')
            ->dataIndicator('submitting')
            ->postx('/register')
            ->content(
                Html::input()->name('name')->validate('required|string|max:255', clientSide: true),
                Html::input()->name('email')->validate('required|email|unique:users', clientSide: true, live: true),
                Html::input()->name('password')->type('password')->validate('required|min:8|confirmed', clientSide: true),
                Html::input()->name('password_confirmation')->type('password')->validate('required', clientSide: true)
            );

        $html = $form->render();

        $this->assertStringContainsString('data-signals', $html);
        $this->assertStringContainsString('data-indicator="submitting"', $html);
        $this->assertStringContainsString('data-error="email"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('minlength="8"', $html);
    }

    /** @test */
    public function it_handles_multi_step_wizard()
    {
        $form = Html::form()
            ->withSignals()
            ->withErrors()
            ->validationGroup('personalInfo', ['name', 'email', 'phone'])
            ->validationGroup('accountInfo', ['username', 'password', 'password_confirmation'])
            ->validationGroup('preferences', ['newsletter', 'notifications'])
            ->content(
                // Step 1
                Html::input()->name('name')->validate('required|string|max:255'),
                Html::input()->name('email')->validate('required|email|unique:users'),
                Html::input()->name('phone')->validate('required|phone'),

                // Step 2
                Html::input()->name('username')->validate('required|alpha_dash|unique:users'),
                Html::input()->name('password')->validate('required|min:8|confirmed'),
                Html::input()->name('password_confirmation')->validate('required'),

                // Step 3
                Html::input()->type('checkbox')->name('newsletter'),
                Html::input()->type('checkbox')->name('notifications')
            );

        $personalRules = $form->getValidationRules('personalInfo');
        $accountRules = $form->getValidationRules('accountInfo');
        $preferenceRules = $form->getValidationRules('preferences');

        $this->assertArrayHasKey('name', $personalRules);
        $this->assertArrayHasKey('email', $personalRules);
        $this->assertArrayHasKey('phone', $personalRules);

        $this->assertArrayHasKey('username', $accountRules);
        $this->assertArrayHasKey('password', $accountRules);
        $this->assertArrayHasKey('password_confirmation', $accountRules);

        // Checkboxes have no validation rules
        $this->assertEquals([], $preferenceRules);
    }
}
