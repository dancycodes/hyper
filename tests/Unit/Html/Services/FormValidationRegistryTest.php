<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Services;

use Dancycodes\Hyper\Html\Services\FormValidationRegistry;
use Dancycodes\Hyper\Tests\TestCase;

class FormValidationRegistryTest extends TestCase
{
    protected FormValidationRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new FormValidationRegistry;
    }

    // ===================================================================
    // Basic Registration Tests
    // ===================================================================

    /** @test */
    public function it_registers_field_rules()
    {
        $this->registry->register('email', 'required|email');

        $this->assertEquals('required|email', $this->registry->getRulesForField('email'));
    }

    /** @test */
    public function it_registers_field_with_messages()
    {
        $this->registry->register('email', 'required|email', [
            'email.required' => 'Email is required',
        ]);

        $this->assertEquals([
            'email.required' => 'Email is required',
        ], $this->registry->getMessagesForField('email'));
    }

    /** @test */
    public function it_registers_field_with_attributes()
    {
        $this->registry->register('user_email', 'required', [], [
            'user_email' => 'email address',
        ]);

        $this->assertEquals([
            'user_email' => 'email address',
        ], $this->registry->getAttributesForField('user_email'));
    }

    /** @test */
    public function it_registers_field_with_all_parameters()
    {
        $this->registry->register(
            'email',
            'required|email',
            ['email.required' => 'Email is required'],
            ['email' => 'email address']
        );

        $this->assertEquals('required|email', $this->registry->getRulesForField('email'));
        $this->assertEquals([
            'email.required' => 'Email is required',
        ], $this->registry->getMessagesForField('email'));
        $this->assertEquals([
            'email' => 'email address',
        ], $this->registry->getAttributesForField('email'));
    }

    // ===================================================================
    // Multiple Fields Tests
    // ===================================================================

    /** @test */
    public function it_registers_multiple_fields()
    {
        $this->registry->register('email', 'required|email');
        $this->registry->register('password', 'required|min:8');

        $this->assertEquals('required|email', $this->registry->getRulesForField('email'));
        $this->assertEquals('required|min:8', $this->registry->getRulesForField('password'));
    }

    /** @test */
    public function it_keeps_fields_independent()
    {
        $this->registry->register('email', 'required|email', ['email.required' => 'Email required']);
        $this->registry->register('password', 'required|min:8', ['password.min' => 'Too short']);

        $this->assertEquals(['email.required' => 'Email required'], $this->registry->getMessagesForField('email'));
        $this->assertEquals(['password.min' => 'Too short'], $this->registry->getMessagesForField('password'));
    }

    // ===================================================================
    // Retrieval Tests
    // ===================================================================

    /** @test */
    public function it_returns_null_for_non_existent_field()
    {
        $this->assertNull($this->registry->getRulesForField('nonexistent'));
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_messages()
    {
        $this->assertEquals([], $this->registry->getMessagesForField('nonexistent'));
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_attributes()
    {
        $this->assertEquals([], $this->registry->getAttributesForField('nonexistent'));
    }

    /** @test */
    public function it_returns_empty_arrays_when_only_rules_registered()
    {
        $this->registry->register('email', 'required|email');

        $this->assertEquals([], $this->registry->getMessagesForField('email'));
        $this->assertEquals([], $this->registry->getAttributesForField('email'));
    }

    // ===================================================================
    // Overwriting Tests
    // ===================================================================

    /** @test */
    public function it_overwrites_existing_field_rules()
    {
        $this->registry->register('email', 'required');
        $this->registry->register('email', 'required|email');

        $this->assertEquals('required|email', $this->registry->getRulesForField('email'));
    }

    /** @test */
    public function it_overwrites_messages()
    {
        $this->registry->register('email', 'required', ['email.required' => 'First message']);
        $this->registry->register('email', 'required', ['email.required' => 'Second message']);

        $this->assertEquals([
            'email.required' => 'Second message',
        ], $this->registry->getMessagesForField('email'));
    }

    /** @test */
    public function it_overwrites_attributes()
    {
        $this->registry->register('email', 'required', [], ['email' => 'first']);
        $this->registry->register('email', 'required', [], ['email' => 'second']);

        $this->assertEquals(['email' => 'second'], $this->registry->getAttributesForField('email'));
    }

    // ===================================================================
    // Clear Tests
    // ===================================================================

    /** @test */
    public function it_clears_all_registered_data()
    {
        $this->registry->register('email', 'required|email');
        $this->registry->register('password', 'required|min:8');

        $this->registry->clear();

        $this->assertNull($this->registry->getRulesForField('email'));
        $this->assertNull($this->registry->getRulesForField('password'));
    }

    /** @test */
    public function it_clears_messages_and_attributes()
    {
        $this->registry->register('email', 'required', ['email.required' => 'Required'], ['email' => 'email']);

        $this->registry->clear();

        $this->assertEquals([], $this->registry->getMessagesForField('email'));
        $this->assertEquals([], $this->registry->getAttributesForField('email'));
    }

    /** @test */
    public function it_allows_re_registration_after_clear()
    {
        $this->registry->register('email', 'required');
        $this->registry->clear();
        $this->registry->register('email', 'required|email');

        $this->assertEquals('required|email', $this->registry->getRulesForField('email'));
    }

    // ===================================================================
    // Real-World Scenarios Tests
    // ===================================================================

    /** @test */
    public function it_handles_live_validation_registration()
    {
        // Simulate multiple inputs with live validation
        $this->registry->register('username', 'required|alpha_dash|unique:users');
        $this->registry->register('email', 'required|email|unique:users');
        $this->registry->register('password', 'required|min:8');

        $this->assertEquals('required|alpha_dash|unique:users', $this->registry->getRulesForField('username'));
        $this->assertEquals('required|email|unique:users', $this->registry->getRulesForField('email'));
        $this->assertEquals('required|min:8', $this->registry->getRulesForField('password'));
    }

    /** @test */
    public function it_handles_custom_messages_for_live_validation()
    {
        $this->registry->register(
            'username',
            'required|unique:users',
            [
                'username.required' => 'Username is required',
                'username.unique' => 'Username already taken',
            ]
        );

        $messages = $this->registry->getMessagesForField('username');

        $this->assertArrayHasKey('username.required', $messages);
        $this->assertArrayHasKey('username.unique', $messages);
    }

    /** @test */
    public function it_handles_multi_step_form_registration()
    {
        // Step 1 fields
        $this->registry->register('name', 'required|string|max:255');
        $this->registry->register('email', 'required|email');

        // Step 2 fields
        $this->registry->register('password', 'required|min:8');
        $this->registry->register('password_confirmation', 'required');

        $this->assertEquals('required|string|max:255', $this->registry->getRulesForField('name'));
        $this->assertEquals('required|min:8', $this->registry->getRulesForField('password'));
    }
}
