<?php

namespace Dancycodes\Hyper\Tests\Unit\Helpers;

use Dancycodes\Hyper\Http\HyperResponse;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test the hyper() helper function
 *
 * @see TESTING.md - File 42: Unit Tests - Helpers
 * Status: ✅ DONE
 */
class HyperHelperTest extends TestCase
{
    /** @test */
    public function test_hyper_helper_returns_hyper_response_instance()
    {
        $response = hyper();

        $this->assertInstanceOf(HyperResponse::class, $response);
    }

    /** @test */
    public function test_hyper_helper_as_singleton()
    {
        $response1 = hyper();
        $response2 = hyper();

        $this->assertSame($response1, $response2, 'hyper() should return the same instance');
    }

    /** @test */
    public function test_hyper_helper_with_no_arguments()
    {
        $response = hyper();

        $this->assertInstanceOf(HyperResponse::class, $response);
        $this->assertIsCallable([$response, 'signals']);
        $this->assertIsCallable([$response, 'view']);
        $this->assertIsCallable([$response, 'fragment']);
    }

    /** @test */
    public function test_hyper_helper_callable_syntax()
    {
        $this->assertTrue(function_exists('hyper'), 'hyper() helper function should exist');
    }
}
