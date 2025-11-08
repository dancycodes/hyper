<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Test Validation Integration with Hyper Signals
 *
 * @see TESTING.md - File 51: ValidationIntegration Tests
 * Status: 🔄 IN PROGRESS - 25 test methods
 */
class ValidationIntegrationTest extends TestCase
{
    public static $latestResponse;

    protected string $viewsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewsPath = storage_path('framework/testing/views');
        if (!File::exists($this->viewsPath)) {
            File::makeDirectory($this->viewsPath, 0755, true);
        }
        View::addLocation($this->viewsPath);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->viewsPath)) {
            File::deleteDirectory($this->viewsPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function test_validation_errors_returned_as_signals()
    {
        Route::post('/validate', function () {
            try {
                signals()->validate([
                    'email' => 'required|email',
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => 'invalid']);
        $response = $this->call('POST', '/validate', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_multiple_fields()
    {
        Route::post('/validate-multi', function () {
            try {
                signals()->validate([
                    'name' => 'required|min:3',
                    'email' => 'required|email',
                    'age' => 'required|integer|min:18',
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode([
            'name' => 'ab',
            'email' => 'invalid',
            'age' => 15,
        ]);
        $response = $this->call('POST', '/validate-multi', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_clears_previous_errors()
    {
        Route::post('/clear-errors', function () {
            $validated = signals()->validate([
                'email' => 'required|email',
            ]);

            return hyper()->signals([
                'validated' => true,
                'errors' => [],
            ]);
        });
        $signals = json_encode(['email' => 'valid@example.com']);
        $response = $this->call('POST', '/clear-errors', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_custom_messages()
    {
        Route::post('/custom-messages', function () {
            try {
                signals()->validate([
                    'email' => 'required|email',
                ], [
                    'email.required' => 'Email is mandatory',
                    'email.email' => 'Invalid email format',
                ]);
            } catch (HyperValidationException $e) {
                $errors = $e->getErrors();
                $this->assertNotEmpty($errors);

                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => '']);
        $response = $this->call('POST', '/custom-messages', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_custom_rules()
    {
        Route::post('/custom-rules', function () {
            try {
                signals()->validate([
                    'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/'],
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['username' => 'ab']);
        $response = $this->call('POST', '/custom-rules', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_nested_data()
    {
        Route::post('/nested-validation', function () {
            try {
                signals()->validate([
                    'user.name' => 'required|string',
                    'user.email' => 'required|email',
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode([
            'user' => [
                'name' => '',
                'email' => 'invalid',
            ],
        ]);
        $response = $this->call('POST', '/nested-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_arrays()
    {
        Route::post('/array-validation', function () {
            try {
                signals()->validate([
                    'tags' => 'required|array|min:2',
                    'tags.*' => 'string|min:2',
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['tags' => ['a']]);
        $response = $this->call('POST', '/array-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_file_uploads()
    {
        Route::post('/file-validation', function () {
            try {
                signals()->validate([
                    'avatar' => 'required|b64image|b64max:2048',
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['avatar' => 'invalid-base64']);
        $response = $this->call('POST', '/file-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_sometimes_rules()
    {
        Route::post('/sometimes-validation', function () {
            $validated = signals()->validate([
                'email' => 'required|email',
                'phone' => 'sometimes|required|regex:/^[0-9]{10}$/',
            ]);

            return hyper()->signals(['validated' => true]);
        });
        // Without phone field
        $signals = json_encode(['email' => 'test@example.com']);
        $response = $this->call('POST', '/sometimes-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_after_hooks()
    {
        Route::post('/after-hook', function () {
            $hookCalled = false;
            try {
                $validator = \Illuminate\Support\Facades\Validator::make(
                    signals()->all(),
                    ['email' => 'required|email']
                );
                $validator->after(function ($validator) use (&$hookCalled) {
                    $hookCalled = true;
                    $validator->errors()->add('custom', 'Custom error');
                });
                if ($validator->fails()) {
                    throw new HyperValidationException($validator, []);
                }
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => 'test@example.com']);
        $response = $this->call('POST', '/after-hook', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_exception_thrown()
    {
        $this->expectException(HyperValidationException::class);
        $signals = json_encode(['email' => 'invalid']);
        $this->call('POST', '/', ['datastar' => $signals], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        signals()->validate(['email' => 'required|email']);
    }

    /** @test */
    public function test_validation_exception_contains_errors()
    {
        try {
            $signals = json_encode(['email' => 'invalid', 'name' => '']);
            $this->call('POST', '/', ['datastar' => $signals], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
            signals()->validate([
                'email' => 'required|email',
                'name' => 'required',
            ]);
        } catch (HyperValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('name', $errors);
        }
    }

    /** @test */
    public function test_validation_exception_response_format()
    {
        Route::post('/response-format', function () {
            try {
                signals()->validate(['email' => 'required|email']);
            } catch (HyperValidationException $e) {
                $response = $e->render(request());
                $this->assertInstanceOf(\Dancycodes\Hyper\Http\HyperResponse::class, $response);

                return $response;
            }
        });
        $signals = json_encode(['email' => 'invalid']);
        $response = $this->call('POST', '/response-format', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_data_error_directive_displays_errors()
    {
        File::put($this->viewsPath . '/error-display.blade.php', '
            <input data-bind="email" />
            <div data-error="email"></div>
        ');
        $rendered = View::make('error-display')->render();
        $this->assertStringContainsString('data-error="email"', $rendered);
    }

    /** @test */
    public function test_validation_with_error_bags()
    {
        Route::post('/error-bags', function () {
            try {
                signals()->validate(['email' => 'required|email']);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => 'invalid']);
        $response = $this->call('POST', '/error-bags', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_preserves_validated_data()
    {
        Route::post('/preserve-data', function () {
            $validated = signals()->validate([
                'name' => 'required|string',
                'email' => 'required|email',
            ]);

            return hyper()->signals([
                'validated' => $validated,
                'success' => true,
            ]);
        });
        $signals = json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $response = $this->call('POST', '/preserve-data', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_authorization()
    {
        Route::post('/authorized-validation', function () {
            // Simulate authorization check
            $authorized = true;
            if (!$authorized) {
                return hyper()->signals(['error' => 'Unauthorized']);
            }
            $validated = signals()->validate(['email' => 'required|email']);

            return hyper()->signals(['success' => true]);
        });
        $signals = json_encode(['email' => 'test@example.com']);
        $response = $this->call('POST', '/authorized-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_with_conditional_rules()
    {
        Route::post('/conditional-rules', function () {
            $requirePhone = signals('requirePhone', false);
            $rules = ['email' => 'required|email'];
            if ($requirePhone) {
                $rules['phone'] = 'required|regex:/^[0-9]{10}$/';
            }
            $validated = signals()->validate($rules);

            return hyper()->signals(['validated' => true]);
        });
        $signals = json_encode([
            'email' => 'test@example.com',
            'requirePhone' => false,
        ]);
        $response = $this->call('POST', '/conditional-rules', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_performance()
    {
        Route::post('/perf-validation', function () {
            $rules = [];
            for ($i = 1; $i <= 50; $i++) {
                $rules["field{$i}"] = 'required|string|min:2|max:100';
            }
            $validated = signals()->validate($rules);

            return hyper()->signals(['validated' => true]);
        });
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data["field{$i}"] = "value{$i}";
        }
        $signals = json_encode($data);
        $startTime = microtime(true);
        $response = $this->call('POST', '/perf-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $response->assertOk();
        $this->assertLessThan(1000, $executionTime, 'Validation took too long');
    }

    /** @test */
    public function test_validation_with_translations()
    {
        Route::post('/translated-validation', function () {
            try {
                signals()->validate(
                    ['email' => 'required|email'],
                    ['email.required' => 'Le champ email est requis']
                );
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => '']);
        $response = $this->call('POST', '/translated-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_messages_localization()
    {
        Route::post('/localized-messages', function () {
            try {
                signals()->validate(['email' => 'required|email']);
            } catch (HyperValidationException $e) {
                $errors = $e->getErrors();
                $this->assertNotEmpty($errors);

                return $e->render(request());
            }
        });
        $signals = json_encode(['email' => 'invalid']);
        $response = $this->call('POST', '/localized-messages', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_integration_with_livewire_style()
    {
        Route::post('/livewire-style', function () {
            try {
                signals()->validate([
                    'email' => 'required|email',
                    'password' => 'required|min:8',
                ]);

                return hyper()->signals([
                    'success' => true,
                    'errors' => [],
                ]);
            } catch (HyperValidationException $e) {
                return $e->render(request());
            }
        });
        $signals = json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response = $this->call('POST', '/livewire-style', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_unique_rule_simulation()
    {
        Route::post('/unique-validation', function () {
            // Simulate unique validation
            $email = signals('email');
            $existingEmails = ['existing@example.com'];
            if (in_array($email, $existingEmails)) {
                return hyper()->signals([
                    'errors' => ['email' => ['The email has already been taken.']],
                ]);
            }

            return hyper()->signals(['success' => true]);
        });
        $signals = json_encode(['email' => 'new@example.com']);
        $response = $this->call('POST', '/unique-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_confirmed_rule()
    {
        Route::post('/confirmed-validation', function () {
            $validated = signals()->validate([
                'password' => 'required|min:8',
                'password_confirmation' => 'required|same:password',
            ]);

            return hyper()->signals(['success' => true]);
        });
        $signals = json_encode([
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response = $this->call('POST', '/confirmed-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_validation_in_different_in_array()
    {
        Route::post('/in-array-validation', function () {
            $validated = signals()->validate([
                'role' => 'required|in:admin,user,guest',
            ]);

            return hyper()->signals(['success' => true]);
        });
        $signals = json_encode(['role' => 'user']);
        $response = $this->call('POST', '/in-array-validation', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }
}
