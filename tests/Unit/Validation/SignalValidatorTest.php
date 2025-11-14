<?php

namespace Dancycodes\Hyper\Tests\Unit\Validation;

use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\Validation\SignalValidator;
use PHPUnit\Framework\TestCase;

/**
 * Signal-Centric Validation Tests
 *
 * Verifies the new signal validation system validates SIGNALS, not form fields.
 */
class SignalValidatorTest extends TestCase
{
    protected SignalValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SignalValidator;
    }

    /** @test */
    public function it_registers_simple_signal_validation(): void
    {
        $this->validator->register('email', 'required|email');

        $this->assertEquals('required|email', $this->validator->getRulesForSignal('email'));
    }

    /** @test */
    public function it_registers_nested_signal_validation(): void
    {
        $this->validator->register('user.email', 'required|email');

        $this->assertEquals('required|email', $this->validator->getRulesForSignal('user.email'));
    }

    /** @test */
    public function it_registers_locked_signal_validation(): void
    {
        $this->validator->register('userId_', 'required|integer');

        $this->assertEquals('required|integer', $this->validator->getRulesForSignal('userId_'));
    }

    /** @test */
    public function it_rejects_local_signal_validation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot register validation for local signal');

        $this->validator->register('_tempEdit', 'required');
    }

    /** @test */
    public function it_validates_simple_signal_successfully(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createMockSignals(['email' => 'test@example.com']);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['email' => 'test@example.com'], $result);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_signal(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createMockSignals(['email' => 'invalid-email']);

        $this->expectException(HyperValidationException::class);

        $this->validator->validate($signals);
    }

    /** @test */
    public function it_validates_nested_signal_successfully(): void
    {
        $this->validator->register('user.email', 'required|email');

        $signals = $this->createMockSignals([
            'user' => [
                'email' => 'test@example.com',
            ],
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['user.email' => 'test@example.com'], $result);
    }

    /** @test */
    public function it_validates_single_signal_successfully(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createMockSignals(['email' => 'test@example.com']);

        $result = $this->validator->validateSingle('email', $signals);

        $this->assertEquals('test@example.com', $result);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_signal(): void
    {
        $signals = $this->createMockSignals(['email' => 'test@example.com']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Signal path \'email\' is not registered for validation');

        $this->validator->validateSingle('email', $signals);
    }

    /** @test */
    public function it_stores_custom_messages(): void
    {
        $messages = ['required' => 'Email is required'];

        $this->validator->register('email', 'required|email', $messages);

        $this->assertEquals($messages, $this->validator->getMessagesForSignal('email'));
    }

    /** @test */
    public function it_stores_custom_attributes(): void
    {
        $attributes = ['email' => 'email address'];

        $this->validator->register('email', 'required|email', [], $attributes);

        $this->assertEquals($attributes, $this->validator->getAttributesForSignal('email'));
    }

    /** @test */
    public function it_clears_all_validation_data(): void
    {
        $this->validator->register('email', 'required|email');
        $this->validator->register('name', 'required|string');

        $this->validator->clear();

        $this->assertNull($this->validator->getRulesForSignal('email'));
        $this->assertNull($this->validator->getRulesForSignal('name'));
    }

    /** @test */
    public function it_validates_multiple_signals_simultaneously(): void
    {
        $this->validator->register('email', 'required|email');
        $this->validator->register('name', 'required|string|min:3');

        $signals = $this->createMockSignals([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ], $result);
    }

    /**
     * Create a mock HyperSignal instance
     */
    protected function createMockSignals(array $data): HyperSignal
    {
        $signals = $this->createMock(HyperSignal::class);

        $signals->method('all')->willReturn($data);

        $signals->method('get')->willReturnCallback(function ($key) use ($data) {
            return $data[$key] ?? null;
        });

        return $signals;
    }
}
