<?php

namespace Dancycodes\Hyper\Tests\Feature\Validation;

use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\Validation\SignalValidator;
use Illuminate\Http\Request;

/**
 * Tests for signals()->validated() method
 *
 * Verifies the new opt-in validation that reads HTML-registered rules.
 */
class ValidatedMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear validator between tests
        app(SignalValidator::class)->clear();
    }

    /** @test */
    public function it_validates_single_signal_with_registered_rules(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('email', 'required|email');

        $signals = $this->createHyperSignal(['email' => 'test@example.com']);

        $result = $signals->validated('email');

        $this->assertEquals('test@example.com', $result);
    }

    /** @test */
    public function it_returns_value_if_no_rules_registered_forgiving_behavior(): void
    {
        // No rules registered for 'email'
        $signals = $this->createHyperSignal(['email' => 'test@example.com']);

        $result = $signals->validated('email');

        $this->assertEquals('test@example.com', $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_single_signal(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('email', 'required|email');

        $signals = $this->createHyperSignal(['email' => 'not-an-email']);

        $this->expectException(HyperValidationException::class);

        $signals->validated('email');
    }

    /** @test */
    public function it_validates_multiple_signals_with_array(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('email', 'required|email');
        $validator->register('name', 'required|string|min:3');

        $signals = $this->createHyperSignal([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        $result = $signals->validated(['email', 'name']);

        $this->assertEquals([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ], $result);
    }

    /** @test */
    public function it_validates_multiple_signals_with_forgiving_behavior(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('email', 'required|email'); // Only email has rules

        $signals = $this->createHyperSignal([
            'email' => 'test@example.com',
            'name' => 'John Doe', // No rules - should still return
        ]);

        $result = $signals->validated(['email', 'name']);

        $this->assertEquals([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ], $result);
    }

    /** @test */
    public function it_validates_all_signals_with_rules_when_null_passed(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('email', 'required|email');
        $validator->register('age', 'required|integer');

        $signals = $this->createHyperSignal([
            'email' => 'test@example.com',
            'age' => 25,
            'unvalidated' => 'ignored', // No rules
        ]);

        $result = $signals->validated();

        $this->assertEquals([
            'email' => 'test@example.com',
            'age' => 25,
        ], $result);
    }

    /** @test */
    public function it_validates_nested_signals(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('user.email', 'required|email');

        $signals = $this->createHyperSignal([
            'user' => ['email' => 'test@example.com'],
        ]);

        $result = $signals->validated('user.email');

        $this->assertEquals('test@example.com', $result);
    }

    /** @test */
    public function it_validates_locked_signals(): void
    {
        $validator = app(SignalValidator::class);
        $validator->register('userId_', 'required|integer');

        $signals = $this->createHyperSignal(['userId_' => 123]);

        $result = $signals->validated('userId_');

        $this->assertEquals(123, $result);
    }

    protected function createHyperSignal(array $data): HyperSignal
    {
        $request = Request::create('/test', 'POST', [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Datastar-Request', 'true');

        return new HyperSignal($request);
    }
}
