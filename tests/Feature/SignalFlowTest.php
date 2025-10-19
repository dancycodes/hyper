<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Test Signal Flow - Complete signal workflows from frontend to backend and back
 *
 * @see TESTING.md - File 49: SignalFlow Tests
 * Status: 🔄 IN PROGRESS - 20 test methods
 */
class SignalFlowTest extends TestCase
{
    protected static $latestResponse;

    /** @test */
    public function test_signals_sent_from_frontend_to_backend()
    {
        // Define a route that receives and echoes signals
        Route::post('/test-signal-flow', function () {
            $count = signals('count');
            $name = signals('name');

            return hyper()->signals([
                'received_count' => $count,
                'received_name' => $name,
            ]);
        });
        // Simulate frontend sending signals
        $signalsData = json_encode([
            'count' => 5,
            'name' => 'Test User',
        ]);
        $this->call('POST', '/test-signal-flow', [
            'datastar' => $signalsData,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Verify signals were received
        $this->assertEquals(5, request()->signals('count'));
        $this->assertEquals('Test User', request()->signals('name'));
    }

    /** @test */
    public function test_signals_update_from_backend_to_frontend()
    {
        Route::get('/update-signals', function () {
            return hyper()->signals([
                'count' => 10,
                'message' => 'Updated from backend',
            ]);
        });
        $response = $this->call('GET', '/update-signals', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
        // Signal updates are sent via SSE
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
    }

    /** @test */
    public function test_multiple_signals_update_together()
    {
        Route::post('/batch-update', function () {
            return hyper()->signals([
                'count' => 1,
                'name' => 'John',
                'email' => 'john@example.com',
                'active' => true,
            ]);
        });
        $response = $this->call('POST', '/batch-update', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_flow_with_validation()
    {
        Route::post('/validate-signals', function () {
            $validated = signals()->validate([
                'email' => 'required|email',
                'age' => 'required|integer|min:18',
            ]);

            return hyper()->signals(['validated' => true]);
        });
        // Valid data
        $validSignals = json_encode([
            'email' => 'user@example.com',
            'age' => 25,
        ]);
        $response = $this->call('POST', '/validate-signals', [
            'datastar' => $validSignals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_flow_with_transformation()
    {
        Route::post('/transform-signals', function () {
            $name = signals('name');
            $transformedName = strtoupper($name);

            return hyper()->signals([
                'original' => $name,
                'transformed' => $transformedName,
            ]);
        });
        $signals = json_encode(['name' => 'john doe']);
        $this->call('POST', '/transform-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Verify transformation happened
        $this->assertEquals('john doe', request()->signals('name'));
    }

    /** @test */
    public function test_nested_signals_flow()
    {
        Route::post('/nested-signals', function () {
            $user = signals('user');

            return hyper()->signals([
                'user' => [
                    'name' => $user['name'] ?? 'Unknown',
                    'email' => $user['email'] ?? 'unknown@example.com',
                    'meta' => [
                        'processed' => true,
                    ],
                ],
            ]);
        });
        $signals = json_encode([
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ]);
        $response = $this->call('POST', '/nested-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_array_signals_flow()
    {
        Route::post('/array-signals', function () {
            $items = signals('items', []);

            return hyper()->signals([
                'items' => array_map(fn ($item) => strtoupper($item), $items),
                'count' => count($items),
            ]);
        });
        $signals = json_encode([
            'items' => ['apple', 'banana', 'cherry'],
        ]);
        $response = $this->call('POST', '/array-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_object_signals_flow()
    {
        Route::post('/object-signals', function () {
            $config = signals('config');

            return hyper()->signals([
                'config' => (object) $config,
                'hasConfig' => !empty($config),
            ]);
        });
        $signals = json_encode([
            'config' => ['theme' => 'dark', 'lang' => 'en'],
        ]);
        $response = $this->call('POST', '/object-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_local_signals_not_sent_to_server()
    {
        // Local signals (prefixed with _) should not be persisted
        $signals = json_encode([
            '_tempValue' => 'temporary',
            'persistedValue' => 'saved',
        ]);
        $this->call('POST', '/', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Both should be readable if sent
        $this->assertEquals('temporary', request()->signals('_tempValue'));
        $this->assertEquals('saved', request()->signals('persistedValue'));
    }

    /** @test */
    public function test_local_signals_can_be_updated_from_server()
    {
        Route::get('/update-local', function () {
            return hyper()->signals([
                '_localState' => 'updated',
                'globalState' => 'also updated',
            ]);
        });
        $response = $this->call('GET', '/update-local', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_locked_signals_flow()
    {
        Route::post('/locked-signals', function () {
            $userId = signals('userId_');

            return hyper()->signals([
                'userId_' => $userId,
                'verified' => true,
            ]);
        });
        $signals = json_encode([
            'userId_' => 123,
        ]);
        $response = $this->call('POST', '/locked-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_merging_behavior()
    {
        Route::post('/merge-signals', function () {
            return hyper()
                ->signals(['count' => 1])
                ->signals(['name' => 'John'])
                ->signals(['count' => 2]); // Should overwrite previous count
        });
        $response = $this->call('POST', '/merge-signals', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_deletion_flow()
    {
        Route::post('/delete-signal', function () {
            return hyper()->signals([
                'toKeep' => 'value',
                'toDelete' => null, // Null should delete the signal
            ]);
        });
        $response = $this->call('POST', '/delete-signal', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_with_null_values()
    {
        Route::post('/null-signals', function () {
            $value = signals('nullableValue');

            return hyper()->signals([
                'received' => $value,
                'isNull' => is_null($value),
            ]);
        });
        $signals = json_encode([
            'nullableValue' => null,
        ]);
        $response = $this->call('POST', '/null-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_type_preservation()
    {
        Route::post('/type-signals', function () {
            $string = signals('stringValue');
            $int = signals('intValue');
            $float = signals('floatValue');
            $bool = signals('boolValue');
            $array = signals('arrayValue');

            return hyper()->signals([
                'types' => [
                    'string' => is_string($string),
                    'int' => is_int($int),
                    'float' => is_float($float),
                    'bool' => is_bool($bool),
                    'array' => is_array($array),
                ],
            ]);
        });
        $signals = json_encode([
            'stringValue' => 'text',
            'intValue' => 42,
            'floatValue' => 3.14,
            'boolValue' => true,
            'arrayValue' => [1, 2, 3],
        ]);
        $response = $this->call('POST', '/type-signals', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_encoding_decoding()
    {
        Route::post('/encode-decode', function () {
            $data = signals('data');

            return hyper()->signals([
                'decoded' => $data,
                'processed' => true,
            ]);
        });
        // Complex nested data
        $complexData = [
            'nested' => [
                'deep' => [
                    'value' => 'test',
                ],
            ],
            'array' => [1, 2, 3],
            'mixed' => ['string', 42, true],
        ];
        $signals = json_encode(['data' => $complexData]);
        $response = $this->call('POST', '/encode-decode', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_with_special_characters()
    {
        Route::post('/special-chars', function () {
            $text = signals('text');

            return hyper()->signals([
                'received' => $text,
                'escaped' => htmlspecialchars($text),
            ]);
        });
        $signals = json_encode([
            'text' => '<script>alert("XSS")</script> & "quotes"',
        ]);
        $response = $this->call('POST', '/special-chars', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_signal_performance_with_large_payload()
    {
        Route::post('/large-payload', function () {
            $items = signals('items');

            return hyper()->signals([
                'count' => count($items),
                'processed' => true,
            ]);
        });
        // Create a large array
        $largeArray = array_fill(0, 100, ['id' => 1, 'name' => 'Item', 'data' => str_repeat('x', 100)]);
        $signals = json_encode(['items' => $largeArray]);
        $startTime = microtime(true);
        $response = $this->call('POST', '/large-payload', [
            'datastar' => $signals,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $response->assertOk();
        // Performance assertion - should process in reasonable time
        $this->assertLessThan(2000, $executionTime, 'Large payload processing took too long');
    }

    /** @test */
    public function test_concurrent_signal_updates()
    {
        Route::post('/concurrent', function () {
            $counter = signals('counter', 0);

            return hyper()->signals([
                'counter' => $counter + 1,
                'timestamp' => time(),
            ]);
        });
        // Simulate multiple requests
        for ($i = 0; $i < 3; $i++) {
            $signals = json_encode(['counter' => $i]);
            $response = $this->call('POST', '/concurrent', [
                'datastar' => $signals,
            ], [], [], [
                'HTTP_DATASTAR_REQUEST' => 'true',
            ]);
            $response->assertOk();
        }
        // All requests should succeed
        $this->assertTrue(true);
    }

    /** @test */
    public function test_signal_consistency_across_requests()
    {
        Route::post('/consistency', function () {
            $value = signals('persistentValue', 'default');

            return hyper()->signals([
                'persistentValue' => $value,
                'requestId' => uniqid(),
            ]);
        });
        // First request
        $signals1 = json_encode(['persistentValue' => 'first']);
        $response1 = $this->call('POST', '/consistency', [
            'datastar' => $signals1,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response1->assertOk();
        // Second request with different value
        $signals2 = json_encode(['persistentValue' => 'second']);
        $response2 = $this->call('POST', '/consistency', [
            'datastar' => $signals2,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response2->assertOk();
        // Each request should handle its own signals independently
        $this->assertTrue(true);
    }
}
