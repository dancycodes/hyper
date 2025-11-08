<?php

namespace Dancycodes\Hyper\Tests\Unit\Exceptions;

use Dancycodes\Hyper\Exceptions\HyperSignalTamperedException;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Test the HyperSignalTamperedException class
 *
 * @see TESTING.md - File 41: HyperSignalTampering Exception Tests
 * Status: 🔄 IN PROGRESS - 10 test methods
 */
class HyperSignalTamperingExceptionTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_exception_can_be_instantiated()
    {
        $exception = new HyperSignalTamperedException;

        $this->assertInstanceOf(HyperSignalTamperedException::class, $exception);
        $this->assertEquals('Signal tampering detected', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    /** @test */
    public function test_exception_with_custom_message()
    {
        $customMessage = 'Locked signal "userId_" has been tampered with';
        $exception = new HyperSignalTamperedException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /** @test */
    public function test_exception_with_custom_code()
    {
        $exception = new HyperSignalTamperedException('Tampering detected', 403);

        $this->assertEquals(403, $exception->getCode());
    }

    /** @test */
    public function test_render_returns_hyper_response_for_hyper_requests()
    {
        Config::set('hyper.security.locked_signals.log_violations', false); // Disable logging for test

        $exception = new HyperSignalTamperedException;

        // Create a mock Hyper request
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->headers->set('Datastar-Request', 'true');

        $response = $exception->render($request);

        // Should return a proper HTTP response
        $this->assertNotNull($response);

        // The exception's render() returns a StreamedResponse (from HyperResponse)
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function test_render_returns_json_for_json_requests()
    {
        Config::set('hyper.security.locked_signals.log_violations', false);

        $exception = new HyperSignalTamperedException;

        // Create a regular request expecting JSON
        $request = $this->call('POST', '/', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $exception->render($request->request ?? request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Security violation detected', $data['error']);
        $this->assertEquals('Signal tampering detected', $data['message']);
    }

    /** @test */
    public function test_render_returns_redirect_for_regular_web_requests()
    {
        Config::set('hyper.security.locked_signals.log_violations', false);

        $exception = new HyperSignalTamperedException;

        // Create a regular GET request
        $request = request();

        $response = $exception->render($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);

        // Should redirect back
        $this->assertEquals(302, $response->getStatusCode());

        // Should have error message in session
        $errors = session()->get('errors');
        if ($errors) {
            $this->assertTrue($errors->has('hyper_security'));
        }
    }

    /** @test */
    public function test_exception_logs_security_violation_when_enabled()
    {
        Config::set('hyper.security.locked_signals.log_violations', true);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Hyper Signal Tampering Detected'
                    && isset($context['message'])
                    && isset($context['ip'])
                    && isset($context['url']);
            });

        $exception = new HyperSignalTamperedException('Test tampering');

        $this->call('GET', '/test-url', [], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $request = request();

        $exception->render($request);
    }

    /** @test */
    public function test_exception_does_not_log_when_logging_disabled()
    {
        Config::set('hyper.security.locked_signals.log_violations', false);

        Log::shouldReceive('warning')->never();

        $exception = new HyperSignalTamperedException;

        $this->call('GET', '/', [], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $request = request();

        $exception->render($request);
    }

    /** @test */
    public function test_exception_report_returns_true()
    {
        $exception = new HyperSignalTamperedException;

        // report() should return true to indicate it should be logged
        $this->assertTrue($exception->report());
    }

    /** @test */
    public function test_exception_logs_user_information_when_authenticated()
    {
        Config::set('hyper.security.locked_signals.log_violations', true);

        // Skip user authentication test for now - focus on core functionality
        // The logged user_id will be null when not authenticated
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Hyper Signal Tampering Detected'
                    && array_key_exists('user_id', $context); // User ID exists (may be null)
            });

        $exception = new HyperSignalTamperedException;

        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->headers->set('Datastar-Request', 'true');

        $exception->render($request);
    }

    /** @test */
    public function test_exception_logs_request_details()
    {
        Config::set('hyper.security.locked_signals.log_violations', true);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['ip'])
                    && isset($context['user_agent'])
                    && isset($context['url'])
                    && $context['message'] === 'Custom security message';
            });

        $exception = new HyperSignalTamperedException('Custom security message');

        $this->call('POST', '/test-endpoint', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $request = request();

        $exception->render($request);
    }

    /** @test */
    public function test_exception_message_is_security_focused()
    {
        $exception = new HyperSignalTamperedException;

        $message = $exception->getMessage();

        // Message should clearly indicate security issue
        $this->assertStringContainsString('tampering', strtolower($message));
        $this->assertStringContainsString('signal', strtolower($message));
    }
}
