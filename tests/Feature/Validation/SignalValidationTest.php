<?php

namespace Dancycodes\Hyper\Tests\Feature\Validation;

use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\Validation\SignalValidator;
use Illuminate\Http\Request;

/**
 * Signal-Centric Validation Feature Tests
 *
 * Verifies the complete signal validation flow with Laravel integration.
 */
class SignalValidationTest extends TestCase
{
    protected SignalValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(SignalValidator::class);
    }

    /** @test */
    public function it_validates_simple_signal_successfully(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createHyperSignal(['email' => 'test@example.com']);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['email' => 'test@example.com'], $result);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_signal(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createHyperSignal(['email' => 'invalid-email']);

        $this->expectException(HyperValidationException::class);

        $this->validator->validate($signals);
    }

    /** @test */
    public function it_validates_nested_signal_successfully(): void
    {
        $this->validator->register('user.email', 'required|email');

        $signals = $this->createHyperSignal([
            'user' => [
                'email' => 'test@example.com',
            ],
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['user.email' => 'test@example.com'], $result);
    }

    /** @test */
    public function it_validates_deeply_nested_signal(): void
    {
        $this->validator->register('user.profile.bio', 'required|string|max:500');

        $signals = $this->createHyperSignal([
            'user' => [
                'profile' => [
                    'bio' => 'Software developer passionate about Laravel',
                ],
            ],
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['user.profile.bio' => 'Software developer passionate about Laravel'], $result);
    }

    /** @test */
    public function it_validates_locked_signal_successfully(): void
    {
        $this->validator->register('userId_', 'required|integer');

        $signals = $this->createHyperSignal(['userId_' => 123]);

        $result = $this->validator->validate($signals);

        $this->assertEquals(['userId_' => 123], $result);
    }

    /** @test */
    public function it_rejects_local_signal_registration(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot register validation for local signal');

        $this->validator->register('_tempEdit', 'required');
    }

    /** @test */
    public function it_validates_single_signal_successfully(): void
    {
        $this->validator->register('email', 'required|email');

        $signals = $this->createHyperSignal(['email' => 'test@example.com']);

        $result = $this->validator->validateSingle('email', $signals);

        $this->assertEquals('test@example.com', $result);
    }

    /** @test */
    public function it_throws_exception_for_unregistered_signal(): void
    {
        $signals = $this->createHyperSignal(['email' => 'test@example.com']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Signal path \'email\' is not registered for validation');

        $this->validator->validateSingle('email', $signals);
    }

    /** @test */
    public function it_validates_multiple_signals_simultaneously(): void
    {
        $this->validator->register('email', 'required|email');
        $this->validator->register('name', 'required|string|min:3');
        $this->validator->register('age', 'required|integer|min:18');

        $signals = $this->createHyperSignal([
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'age' => 25,
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals([
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'age' => 25,
        ], $result);
    }

    /** @test */
    public function it_validates_with_custom_error_messages(): void
    {
        $this->validator->register(
            'email',
            'required|email',
            ['required' => 'The email field is absolutely required']
        );

        $signals = $this->createHyperSignal(['email' => '']);

        try {
            $this->validator->validate($signals);
            $this->fail('Expected HyperValidationException to be thrown');
        } catch (HyperValidationException $e) {
            $errors = $e->validator->errors();
            $this->assertStringContainsString('absolutely required', $errors->first('email'));
        }
    }

    /** @test */
    public function it_fails_validation_for_nested_signal_with_invalid_data(): void
    {
        $this->validator->register('user.email', 'required|email');

        $signals = $this->createHyperSignal([
            'user' => [
                'email' => 'not-an-email',
            ],
        ]);

        $this->expectException(HyperValidationException::class);

        $this->validator->validate($signals);
    }

    /** @test */
    public function it_validates_mixed_signal_types(): void
    {
        $this->validator->register('email', 'required|email');
        $this->validator->register('user.name', 'required|string');
        $this->validator->register('userId_', 'required|integer');

        $signals = $this->createHyperSignal([
            'email' => 'test@example.com',
            'user' => ['name' => 'John'],
            'userId_' => 123,
        ]);

        $result = $this->validator->validate($signals);

        $this->assertEquals([
            'email' => 'test@example.com',
            'user.name' => 'John',
            'userId_' => 123,
        ], $result);
    }

    /** @test */
    public function it_clears_validation_rules(): void
    {
        $this->validator->register('email', 'required|email');
        $this->validator->register('name', 'required|string');

        $this->validator->clear();

        $this->assertNull($this->validator->getRulesForSignal('email'));
        $this->assertNull($this->validator->getRulesForSignal('name'));
    }

    /**
     * Create a HyperSignal instance with data
     */
    protected function createHyperSignal(array $data): HyperSignal
    {
        // Create a request with signal data in body (simulating Datastar request)
        $json = json_encode($data);
        $request = Request::create('/test', 'POST', [], [], [], [], $json);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Datastar-Request', 'true');

        return new HyperSignal($request);
    }
}
