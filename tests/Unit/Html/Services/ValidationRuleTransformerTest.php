<?php

namespace Dancycodes\Hyper\Tests\Unit\Html\Services;

use Dancycodes\Hyper\Html\Services\ValidationRuleTransformer;
use Dancycodes\Hyper\Tests\TestCase;

class ValidationRuleTransformerTest extends TestCase
{
    // ===================================================================
    // Basic Rules Tests
    // ===================================================================

    /** @test */
    public function it_transforms_required_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required');

        $this->assertEquals(['required' => ''], $result);
    }

    /** @test */
    public function it_transforms_email_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('email');

        $this->assertEquals(['type' => 'email'], $result);
    }

    /** @test */
    public function it_transforms_url_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('url');

        $this->assertEquals(['type' => 'url'], $result);
    }

    /** @test */
    public function it_transforms_numeric_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('numeric');

        $this->assertEquals(['type' => 'number'], $result);
    }

    /** @test */
    public function it_transforms_integer_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('integer');

        $this->assertEquals(['type' => 'number'], $result);
    }

    // ===================================================================
    // Min/Max Rules Tests
    // ===================================================================

    /** @test */
    public function it_transforms_min_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('min:8');

        $this->assertEquals(['minlength' => '8', 'min' => '8'], $result);
    }

    /** @test */
    public function it_transforms_max_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('max:255');

        $this->assertEquals(['maxlength' => '255', 'max' => '255'], $result);
    }

    /** @test */
    public function it_transforms_between_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('between:5,10');

        $this->assertEquals([
            'minlength' => '5',
            'maxlength' => '10',
            'min' => '5',
            'max' => '10',
        ], $result);
    }

    /** @test */
    public function it_handles_between_rule_with_spaces()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('between:5, 10');

        $this->assertEquals([
            'minlength' => '5',
            'maxlength' => '10',
            'min' => '5',
            'max' => '10',
        ], $result);
    }

    // ===================================================================
    // Regex Rules Tests
    // ===================================================================

    /** @test */
    public function it_transforms_simple_regex()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('regex:/[A-Z]+/');

        $this->assertEquals(['pattern' => '[A-Z]+'], $result);
    }

    /** @test */
    public function it_transforms_complex_regex_pattern()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('regex:/^[a-zA-Z0-9_-]+$/');

        $this->assertEquals(['pattern' => '^[a-zA-Z0-9_-]+$'], $result);
    }

    // ===================================================================
    // Combined Rules Tests
    // ===================================================================

    /** @test */
    public function it_combines_multiple_rules()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|email|max:255');

        $this->assertEquals([
            'required' => '',
            'type' => 'email',
            'maxlength' => '255',
            'max' => '255',
        ], $result);
    }

    /** @test */
    public function it_combines_required_min_max()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|min:3|max:20');

        $this->assertEquals([
            'required' => '',
            'minlength' => '3',
            'min' => '3',
            'maxlength' => '20',
            'max' => '20',
        ], $result);
    }

    /** @test */
    public function it_handles_all_transformable_rules_together()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|email|min:5|max:255');

        $this->assertEquals([
            'required' => '',
            'type' => 'email',
            'minlength' => '5',
            'min' => '5',
            'maxlength' => '255',
            'max' => '255',
        ], $result);
    }

    // ===================================================================
    // Non-Transformable Rules Tests
    // ===================================================================

    /** @test */
    public function it_ignores_unique_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('unique:users');

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_ignores_alpha_dash_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('alpha_dash');

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_ignores_confirmed_rule()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('confirmed');

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_keeps_transformable_rules_when_mixed_with_non_transformable()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|alpha_dash|unique:users');

        $this->assertEquals(['required' => ''], $result);
    }

    // ===================================================================
    // Edge Cases Tests
    // ===================================================================

    /** @test */
    public function it_handles_empty_string()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('');

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_handles_rules_with_extra_spaces()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required | email | max:255');

        $this->assertEquals([
            'required' => '',
            'type' => 'email',
            'maxlength' => '255',
            'max' => '255',
        ], $result);
    }

    /** @test */
    public function it_handles_single_pipe()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|');

        $this->assertEquals(['required' => ''], $result);
    }

    /** @test */
    public function it_handles_multiple_consecutive_pipes()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required||email');

        $this->assertEquals([
            'required' => '',
            'type' => 'email',
        ], $result);
    }

    // ===================================================================
    // Real-World Scenarios Tests
    // ===================================================================

    /** @test */
    public function it_transforms_login_email_field()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|email|max:255');

        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('maxlength', $result);
        $this->assertArrayHasKey('max', $result);
        $this->assertEquals('email', $result['type']);
    }

    /** @test */
    public function it_transforms_password_field()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|min:8|max:255');

        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('minlength', $result);
        $this->assertArrayHasKey('min', $result);
        $this->assertArrayHasKey('maxlength', $result);
        $this->assertArrayHasKey('max', $result);
        $this->assertEquals('8', $result['minlength']);
    }

    /** @test */
    public function it_transforms_username_field()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|alpha_dash|min:3|max:20|unique:users');

        $this->assertEquals([
            'required' => '',
            'minlength' => '3',
            'min' => '3',
            'maxlength' => '20',
            'max' => '20',
        ], $result);
    }

    /** @test */
    public function it_transforms_url_field()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|url|max:2048');

        $this->assertEquals([
            'required' => '',
            'type' => 'url',
            'maxlength' => '2048',
            'max' => '2048',
        ], $result);
    }

    /** @test */
    public function it_transforms_age_number_field()
    {
        $result = ValidationRuleTransformer::toHtml5Attributes('required|integer|min:18|max:120');

        $this->assertEquals([
            'required' => '',
            'type' => 'number',
            'minlength' => '18',
            'min' => '18',
            'maxlength' => '120',
            'max' => '120',
        ], $result);
    }
}
