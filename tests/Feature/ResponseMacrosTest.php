<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Http\HyperResponse;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Test Response Macros Integration
 *
 * @see TESTING.md - File 48: ResponseMacros Tests
 * Status: 🔄 IN PROGRESS - 3 test methods
 */
class ResponseMacrosTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_hyper_macro_returns_instance()
    {
        // response()->hyper() should return HyperResponse instance
        $hyperResponse = response()->hyper();
        $this->assertInstanceOf(HyperResponse::class, $hyperResponse);
    }

    /** @test */
    public function test_response_hyper_macro_available()
    {
        // The macro should be callable without errors
        $hyperResponse = response()->hyper();
        $this->assertInstanceOf(HyperResponse::class, $hyperResponse);
        // Multiple calls should return the same singleton instance
        $hyper1 = response()->hyper();
        $hyper2 = response()->hyper();
        $this->assertSame($hyper1, $hyper2);
    }

    /** @test */
    public function test_hyper_macro_in_controller()
    {
        // Test that response()->hyper() can be used in a route
        $executed = false;
        Route::get('/test-response-macro', function () use (&$executed) {
            $hyper = response()->hyper();
            // Verify it's a HyperResponse instance
            if ($hyper instanceof HyperResponse) {
                $executed = true;
            }

            return $hyper->signals(['test' => 'value']);
        });
        // Make a Hyper request to the route
        $this->call('GET', '/test-response-macro', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Should have executed and returned correct type
        $this->assertTrue($executed, 'response()->hyper() did not return HyperResponse in route');
    }
}
