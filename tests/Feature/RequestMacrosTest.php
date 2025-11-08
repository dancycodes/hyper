<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Test Request Macros Integration
 *
 * @see TESTING.md - File 47: RequestMacros Tests
 * Status: 🔄 IN PROGRESS - 12 test methods
 */
class RequestMacrosTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_is_hyper_macro_detects_hyper_requests()
    {
        // Create a Hyper request
        $request = Request::create('/', 'GET');
        $request->headers->set('Datastar-Request', 'true');
        // Should detect as Hyper request
        $this->assertTrue($request->isHyper());
    }

    /** @test */
    public function test_is_hyper_macro_detects_normal_requests()
    {
        // Create a normal request
        $request = Request::create('/', 'GET');
        // Should NOT detect as Hyper request
        $this->assertFalse($request->isHyper());
    }

    /** @test */
    public function test_signals_macro_returns_signal_manager()
    {
        $request = Request::create('/', 'GET');
        // signals() without parameters should return HyperSignal instance
        $signals = $request->signals();
        $this->assertInstanceOf(HyperSignal::class, $signals);
    }

    /** @test */
    public function test_signals_macro_with_key()
    {
        // Simulate a Hyper request with properly encoded signals data
        $signalsData = json_encode([
            'count' => 5,
            'name' => 'Test',
        ]);
        $this->call('POST', '/', [
            'datastar' => $signalsData,
        ], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Use the global request() helper which has the signals data
        $count = request()->signals('count');
        $this->assertEquals(5, $count);
        $name = request()->signals('name');
        $this->assertEquals('Test', $name);
    }

    /** @test */
    public function test_signals_macro_with_key_and_default()
    {
        $request = Request::create('/', 'GET');
        // signals($key, $default) should return default for missing key
        $result = $request->signals('missing', 'default-value');
        $this->assertEquals('default-value', $result);
    }

    /** @test */
    public function test_is_hyper_navigate_macro_without_key()
    {
        // Create a request without HYPER-NAVIGATE header
        $request = Request::create('/', 'GET');
        $this->assertFalse($request->isHyperNavigate());
        // Create a request with HYPER-NAVIGATE header
        $navigateRequest = Request::create('/', 'GET');
        $navigateRequest->headers->set('HYPER-NAVIGATE', 'true');
        $this->assertTrue($navigateRequest->isHyperNavigate());
    }

    /** @test */
    public function test_is_hyper_navigate_macro_with_single_key()
    {
        // Create a request with HYPER-NAVIGATE and specific key
        $request = Request::create('/', 'GET');
        $request->headers->set('HYPER-NAVIGATE', 'true');
        $request->headers->set('HYPER-NAVIGATE-KEY', 'sidebar');
        // Should match the exact key
        $this->assertTrue($request->isHyperNavigate('sidebar'));
        // Should not match different key
        $this->assertFalse($request->isHyperNavigate('header'));
    }

    /** @test */
    public function test_is_hyper_navigate_macro_with_multiple_keys()
    {
        // Create a request with HYPER-NAVIGATE and multiple keys
        $request = Request::create('/', 'GET');
        $request->headers->set('HYPER-NAVIGATE', 'true');
        $request->headers->set('HYPER-NAVIGATE-KEY', 'sidebar, header');
        // Should match if any key matches
        $this->assertTrue($request->isHyperNavigate(['sidebar', 'footer']));
        $this->assertTrue($request->isHyperNavigate(['header']));
        // Should not match if no keys match
        $this->assertFalse($request->isHyperNavigate(['footer', 'nav']));
    }

    /** @test */
    public function test_hyper_navigate_key_macro()
    {
        // Request without navigate key
        $request = Request::create('/', 'GET');
        $this->assertNull($request->hyperNavigateKey());
        // Request with navigate key
        $navigateRequest = Request::create('/', 'GET');
        $navigateRequest->headers->set('HYPER-NAVIGATE-KEY', 'sidebar');
        $this->assertEquals('sidebar', $navigateRequest->hyperNavigateKey());
    }

    /** @test */
    public function test_hyper_navigate_keys_macro()
    {
        // Request without navigate keys
        $request = Request::create('/', 'GET');
        $this->assertEquals([], $request->hyperNavigateKeys());
        // Request with single key
        $singleKeyRequest = Request::create('/', 'GET');
        $singleKeyRequest->headers->set('HYPER-NAVIGATE-KEY', 'sidebar');
        $this->assertEquals(['sidebar'], $singleKeyRequest->hyperNavigateKeys());
        // Request with multiple keys
        $multiKeyRequest = Request::create('/', 'GET');
        $multiKeyRequest->headers->set('HYPER-NAVIGATE-KEY', 'sidebar, header, footer');
        $this->assertEquals(['sidebar', 'header', 'footer'], $multiKeyRequest->hyperNavigateKeys());
    }

    /** @test */
    public function test_macros_available_in_routes()
    {
        // Define a test route that uses the macros
        Route::get('/test-macros', function (Request $request) {
            return response()->json([
                'isHyper' => $request->isHyper(),
                'isHyperNavigate' => $request->isHyperNavigate(),
                'navigateKey' => $request->hyperNavigateKey(),
                'navigateKeys' => $request->hyperNavigateKeys(),
            ]);
        });
        // Make a request to the route
        $response = $this->get('/test-macros');
        // Should execute without errors
        $response->assertOk();
        $response->assertJson([
            'isHyper' => false,
            'isHyperNavigate' => false,
            'navigateKey' => null,
            'navigateKeys' => [],
        ]);
    }

    /** @test */
    public function test_macros_available_in_middleware()
    {
        // Test that macros are available on the Request class globally
        // This ensures they work in middleware context as well
        // Create a Hyper request to test the macros
        $request = Request::create('/', 'GET');
        $request->headers->set('Datastar-Request', 'true');
        // Set the request in the container
        $this->app->instance('request', $request);
        // Test that all macros work on the request
        $this->assertTrue($request->isHyper());
        $this->assertInstanceOf(HyperSignal::class, $request->signals());
        $this->assertFalse($request->isHyperNavigate());
        $this->assertNull($request->hyperNavigateKey());
        $this->assertIsArray($request->hyperNavigateKeys());
    }
}
