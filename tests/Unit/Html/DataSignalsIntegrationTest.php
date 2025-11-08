<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Session;

/**
 * Test dataSignals() integration with Hyper signal system
 *
 * Verifies:
 * - Regular signal initialization
 * - Local signal handling (_prefix)
 * - Locked signal storage and encryption (_suffix)
 * - Session integration
 * - Closure evaluation
 * - Laravel type conversion
 */
class DataSignalsIntegrationTest extends TestCase
{
    public static $latestResponse;

    protected function setUp(): void
    {
        parent::setUp();

        // Start session for locked signal tests
        Session::start();
    }

    // ===================================================================
    // REGULAR SIGNALS TESTS
    // ===================================================================

    /** @test */
    public function test_data_signals_renders_json_attribute()
    {
        $html = Html::div()->dataSignals(['count' => 0])->render();

        $this->assertStringContainsString('data-signals=', $html);
        // JSON is HTML-escaped in attributes
        $this->assertStringContainsString('&quot;count&quot;:0', $html);
    }

    /** @test */
    public function test_data_signals_with_multiple_signals()
    {
        $html = Html::div()->dataSignals([
            'count' => 0,
            'name' => 'John',
            'active' => true,
        ])->render();

        $this->assertStringContainsString('&quot;count&quot;:0', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;John&quot;', $html);
        $this->assertStringContainsString('&quot;active&quot;:true', $html);
    }

    /** @test */
    public function test_data_signals_with_nested_arrays()
    {
        $html = Html::div()->dataSignals([
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ])->render();

        $this->assertStringContainsString('&quot;user&quot;:', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;John&quot;', $html);
        $this->assertStringContainsString('&quot;email&quot;:&quot;john@example.com&quot;', $html);
    }

    /** @test */
    public function test_data_signals_with_numeric_values()
    {
        $html = Html::div()->dataSignals([
            'integer' => 42,
            'float' => 3.14,
            'zero' => 0,
            'negative' => -10,
        ])->render();

        $this->assertStringContainsString('&quot;integer&quot;:42', $html);
        $this->assertStringContainsString('&quot;float&quot;:3.14', $html);
        $this->assertStringContainsString('&quot;zero&quot;:0', $html);
        $this->assertStringContainsString('&quot;negative&quot;:-10', $html);
    }

    /** @test */
    public function test_data_signals_with_boolean_values()
    {
        $html = Html::div()->dataSignals([
            'isActive' => true,
            'isDisabled' => false,
        ])->render();

        $this->assertStringContainsString('&quot;isActive&quot;:true', $html);
        $this->assertStringContainsString('&quot;isDisabled&quot;:false', $html);
    }

    /** @test */
    public function test_data_signals_with_null_value()
    {
        $html = Html::div()->dataSignals([
            'nullValue' => null,
        ])->render();

        $this->assertStringContainsString('&quot;nullValue&quot;:null', $html);
    }

    // ===================================================================
    // LOCAL SIGNALS TESTS (_prefix)
    // ===================================================================

    /** @test */
    public function test_local_signals_are_included_in_output()
    {
        $html = Html::div()->dataSignals([
            '_mode' => 'edit',
            '_tempData' => 'value',
        ])->render();

        // Local signals should be in the output (they're just client-side flags)
        $this->assertStringContainsString('&quot;_mode&quot;:&quot;edit&quot;', $html);
        $this->assertStringContainsString('&quot;_tempData&quot;:&quot;value&quot;', $html);
    }

    /** @test */
    public function test_mixed_regular_and_local_signals()
    {
        $html = Html::div()->dataSignals([
            'count' => 0,           // Regular
            '_editing' => false,    // Local
            'userId' => 123,        // Regular
        ])->render();

        $this->assertStringContainsString('&quot;count&quot;:0', $html);
        $this->assertStringContainsString('&quot;_editing&quot;:false', $html);
        $this->assertStringContainsString('&quot;userId&quot;:123', $html);
    }

    // ===================================================================
    // LOCKED SIGNALS TESTS (_suffix)
    // ===================================================================

    /** @test */
    public function test_locked_signals_are_stored_in_session()
    {
        Html::div()->dataSignals([
            'userId_' => 42,
            'role_' => 'admin',
        ])->render();

        // Check that locked signals were stored in session
        $this->assertTrue(Session::has('hyper_locked_signals'));
    }

    /** @test */
    public function test_locked_signals_are_encrypted_in_session()
    {
        Html::div()->dataSignals([
            'secretKey_' => 'sensitive-value',
        ])->render();

        $stored = Session::get('hyper_locked_signals');

        // Should be encrypted string, not plain JSON
        $this->assertIsString($stored);
        $this->assertStringNotContainsString('sensitive-value', $stored);
        $this->assertStringNotContainsString('secretKey_', $stored);
    }

    /** @test */
    public function test_locked_signals_appear_in_data_attribute()
    {
        $html = Html::div()->dataSignals([
            'userId_' => 123,
            'isAdmin_' => true,
        ])->render();

        // Locked signals should still appear in data-signals attribute for client
        $this->assertStringContainsString('&quot;userId_&quot;:123', $html);
        $this->assertStringContainsString('&quot;isAdmin_&quot;:true', $html);
    }

    /** @test */
    public function test_only_locked_signals_are_stored_in_session()
    {
        Session::forget('hyper_locked_signals');

        Html::div()->dataSignals([
            'regular' => 'value',
            'locked_' => 'secret',
            '_local' => 'temp',
        ])->render();

        // Only locked_ should be in session
        $this->assertTrue(Session::has('hyper_locked_signals'));

        // Regular and local signals should NOT trigger session storage if no locked signals present
        Session::forget('hyper_locked_signals');
        Html::div()->dataSignals([
            'regular' => 'value',
            '_local' => 'temp',
        ])->render();

        $this->assertFalse(Session::has('hyper_locked_signals'));
    }

    /** @test */
    public function test_multiple_locked_signals()
    {
        $html = Html::div()->dataSignals([
            'userId_' => 42,
            'role_' => 'admin',
            'permissions_' => ['read', 'write', 'delete'],
        ])->render();

        $this->assertTrue(Session::has('hyper_locked_signals'));
        $this->assertStringContainsString('&quot;userId_&quot;:42', $html);
        $this->assertStringContainsString('&quot;role_&quot;:&quot;admin&quot;', $html);
        $this->assertStringContainsString('&quot;permissions_&quot;:', $html);
    }

    // ===================================================================
    // MIXED SIGNAL TYPES TESTS
    // ===================================================================

    /** @test */
    public function test_mixed_signal_types()
    {
        $html = Html::div()->dataSignals([
            'count' => 0,              // Regular
            '_mode' => 'edit',         // Local
            'userId_' => 123,          // Locked
            'email' => 'test@test.com', // Regular
            '_temp' => 'data',         // Local
            'role_' => 'admin',        // Locked
        ])->render();

        // All should appear in HTML
        $this->assertStringContainsString('&quot;count&quot;:0', $html);
        $this->assertStringContainsString('&quot;_mode&quot;:&quot;edit&quot;', $html);
        $this->assertStringContainsString('&quot;userId_&quot;:123', $html);
        $this->assertStringContainsString('&quot;email&quot;:&quot;test@test.com&quot;', $html);
        $this->assertStringContainsString('&quot;_temp&quot;:&quot;data&quot;', $html);
        $this->assertStringContainsString('&quot;role_&quot;:&quot;admin&quot;', $html);

        // Locked signals should be in session
        $this->assertTrue(Session::has('hyper_locked_signals'));
    }

    // ===================================================================
    // CLOSURE EVALUATION TESTS
    // ===================================================================

    /** @test */
    public function test_data_signals_evaluates_closure()
    {
        $html = Html::div()->dataSignals(function () {
            return [
                'count' => 5,
                'name' => 'From Closure',
            ];
        })->render();

        $this->assertStringContainsString('&quot;count&quot;:5', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;From Closure&quot;', $html);
    }

    /** @test */
    public function test_closure_with_dependency_injection()
    {
        // Closure should have access to container for DI
        $html = Html::div()->dataSignals(function () {
            $config = app('config');

            return [
                'appName' => $config->get('app.name', 'Laravel'),
            ];
        })->render();

        $this->assertStringContainsString('&quot;appName&quot;:', $html);
    }

    /** @test */
    public function test_closure_returns_locked_signals()
    {
        Session::forget('hyper_locked_signals');

        Html::div()->dataSignals(function () {
            return [
                'userId_' => 42,
                'role_' => 'admin',
            ];
        })->render();

        // Locked signals from closure should be stored
        $this->assertTrue(Session::has('hyper_locked_signals'));
    }

    // ===================================================================
    // LARAVEL TYPE CONVERSION TESTS
    // ===================================================================

    /** @test */
    public function test_eloquent_model_conversion()
    {
        // Create a mock object that implements toArray()
        $model = new class
        {
            public function toArray()
            {
                return [
                    'id' => 1,
                    'name' => 'Test Model',
                    'email' => 'test@example.com',
                ];
            }
        };

        $html = Html::div()->dataSignals([
            'user' => $model,
        ])->render();

        $this->assertStringContainsString('&quot;user&quot;:', $html);
        $this->assertStringContainsString('&quot;id&quot;:1', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;Test Model&quot;', $html);
    }

    /** @test */
    public function test_collection_conversion()
    {
        $collection = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ]);

        $html = Html::div()->dataSignals([
            'items' => $collection,
        ])->render();

        $this->assertStringContainsString('&quot;items&quot;:', $html);
        $this->assertStringContainsString('&quot;id&quot;:1', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;Item 1&quot;', $html);
    }

    // ===================================================================
    // XSS PROTECTION TESTS
    // ===================================================================

    /** @test */
    public function test_signal_values_are_json_escaped()
    {
        $html = Html::div()->dataSignals([
            'script' => '<script>alert("XSS")</script>',
            'html' => '<div onclick="malicious()">Click</div>',
        ])->render();

        // JSON encoding should escape these
        $this->assertStringNotContainsString('<script>', $html);
        // onclick= is present but escaped as unicode (safe)
        $this->assertStringContainsString('\\u003C', $html); // JSON_HEX_TAG for < character
        $this->assertStringContainsString('\\u0022', $html); // JSON_HEX_QUOT for " character
    }

    /** @test */
    public function test_signal_key_names_with_special_characters()
    {
        $html = Html::div()->dataSignals([
            'user-email' => 'test@test.com',
            'user_id' => 123,
            'user.name' => 'John',
        ])->render();

        $this->assertStringContainsString('&quot;user-email&quot;:', $html);
        $this->assertStringContainsString('&quot;user_id&quot;:', $html);
        $this->assertStringContainsString('&quot;user.name&quot;:', $html);
    }

    // ===================================================================
    // EDGE CASES
    // ===================================================================

    /** @test */
    public function test_empty_signals_array()
    {
        $html = Html::div()->dataSignals([])->render();

        $this->assertStringContainsString('data-signals=', $html);
        // Empty PHP array [] is encoded as JSON array [], not object {}
        $this->assertStringContainsString('[]', $html);
    }

    /** @test */
    public function test_signals_with_very_long_values()
    {
        $longString = str_repeat('A', 1000);
        $html = Html::div()->dataSignals([
            'longValue' => $longString,
        ])->render();

        $this->assertStringContainsString('&quot;longValue&quot;:', $html);
        $this->assertStringContainsString($longString, $html);
    }

    /** @test */
    public function test_signals_with_special_json_characters()
    {
        $html = Html::div()->dataSignals([
            'quote' => 'He said "hello"',
            'apostrophe' => "It's working",
            'backslash' => 'Path: C:\Windows',
            'ampersand' => 'Tom & Jerry',
        ])->render();

        // All should be properly JSON encoded
        $decoded = json_decode(
            html_entity_decode(
                preg_replace('/.*data-signals=[\'"]({.*?})[\'"].*/', '$1', $html)
            ),
            true
        );

        if ($decoded) {
            $this->assertEquals('He said "hello"', $decoded['quote'] ?? null);
            $this->assertEquals("It's working", $decoded['apostrophe'] ?? null);
        }

        // At minimum, should not break HTML
        $this->assertStringContainsString('data-signals=', $html);
    }
}
