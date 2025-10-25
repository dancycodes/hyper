<?php

namespace Dancycodes\Hyper\Tests\Unit\Http\Middleware;

use Dancycodes\Hyper\Http\Middleware\ConvertRedirectsForDatastar;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConvertRedirectsForDatastarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Start session for flash data tests
        session()->start();

        // Set up Hyper request for middleware tests
        request()->headers->set('Datastar-Request', 'true');
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        session()->flush();

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_passes_through_non_datastar_requests()
    {
        // Remove Datastar header for this specific test
        request()->headers->remove('Datastar-Request');

        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * @test
     */
    public function it_passes_through_non_redirect_responses_for_datastar_requests()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new Response('<div>Content</div>', 200);

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     */
    public function it_converts_redirect_response_to_sse_for_datastar_requests()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertInstanceOf(StreamedResponse::class, $result);
        $this->assertEquals('text/event-stream', $result->headers->get('Content-Type'));
        // Cache-Control header may include 'private' in addition to 'no-cache' (Laravel default)
        $this->assertStringContainsString('no-cache', $result->headers->get('Cache-Control'));
    }

    /**
     * @test
     */
    public function it_skips_responses_already_processed_by_hyper_redirect()
    {
        $middleware = new ConvertRedirectsForDatastar;

        $response = new StreamedResponse;
        $response->headers->set('X-Hyper-Response', 'true');
        $response->setStatusCode(302);

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }

    /**
     * @test
     */
    public function it_detects_redirect_response_instance()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertInstanceOf(StreamedResponse::class, $result);
    }

    /**
     * @test
     *
     * @dataProvider redirectStatusCodesProvider
     */
    public function it_detects_redirect_status_codes($statusCode)
    {
        $middleware = new ConvertRedirectsForDatastar;

        $response = new Response('', $statusCode);
        $response->headers->set('Location', '/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $this->assertInstanceOf(StreamedResponse::class, $result);
    }

    public static function redirectStatusCodesProvider(): array
    {
        return [
            '201 Created' => [201],
            '301 Moved Permanently' => [301],
            '302 Found' => [302],
            '303 See Other' => [303],
            '307 Temporary Redirect' => [307],
            '308 Permanent Redirect' => [308],
        ];
    }

    /**
     * @test
     */
    public function it_generates_javascript_navigation_script()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/dashboard');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $events = $this->getSSEEvents($result);

        // Verify we have events
        $this->assertGreaterThan(0, count($events), 'Should have at least one SSE event');

        // Verify JavaScript includes the target URL and setTimeout
        $hasWindowLocation = false;
        $hasTargetUrl = false;
        $hasSetTimeout = false;

        foreach ($events as $event) {
            if (isset($event['data'])) {
                if (str_contains($event['data'], 'window.location')) {
                    $hasWindowLocation = true;
                }
                if (str_contains($event['data'], '/dashboard')) {
                    $hasTargetUrl = true;
                }
                if (str_contains($event['data'], 'setTimeout')) {
                    $hasSetTimeout = true;
                }
            }
        }

        $this->assertTrue($hasWindowLocation, 'JavaScript should include window.location');
        $this->assertTrue($hasTargetUrl, 'JavaScript should include target URL /dashboard');
        $this->assertTrue($hasSetTimeout, 'JavaScript should include setTimeout');
    }

    /**
     * @test
     */
    public function it_uses_200ms_delay_for_navigation()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $events = $this->getSSEEvents($result);

        // Verify 200ms delay is used in setTimeout
        $has200msDelay = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], ', 200)')) {
                $has200msDelay = true;
                break;
            }
        }

        $this->assertTrue($has200msDelay, 'Navigation script should use 200ms delay');
    }

    /**
     * @test
     */
    public function it_escapes_url_in_javascript()
    {
        $middleware = new ConvertRedirectsForDatastar;
        $response = new RedirectResponse('/search?q=test&category=books');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        // Verify response is a StreamedResponse with SSE content
        $this->assertInstanceOf(StreamedResponse::class, $result);

        $events = $this->getSSEEvents($result);
        $this->assertGreaterThan(0, count($events), 'Should have at least one SSE event');

        // Verify URL components are present in the JavaScript
        $hasUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) &&
                (str_contains($event['data'], 'search') || str_contains($event['data'], '/search')) &&
                str_contains($event['data'], 'test') &&
                str_contains($event['data'], 'books')) {
                $hasUrl = true;
                break;
            }
        }

        $this->assertTrue($hasUrl, 'URL with query parameters should be in the navigation script');
    }

    /**
     * @test
     */
    public function it_extracts_url_from_location_header()
    {
        $middleware = new ConvertRedirectsForDatastar;

        $response = new Response('', 302);
        $response->headers->set('Location', '/custom-target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        // Verify response is a StreamedResponse
        $this->assertInstanceOf(StreamedResponse::class, $result);

        $events = $this->getSSEEvents($result);
        $this->assertGreaterThan(0, count($events), 'Should have at least one SSE event');

        // Verify custom target URL is in the script
        $hasCustomTarget = false;
        foreach ($events as $event) {
            if (isset($event['data']) &&
                (str_contains($event['data'], 'custom-target') || str_contains($event['data'], '/custom-target'))) {
                $hasCustomTarget = true;
                break;
            }
        }

        $this->assertTrue($hasCustomTarget, 'Custom target URL from Location header should be in the script');
    }

    /**
     * @test
     */
    public function it_preserves_session_flash_data_through_redirect()
    {
        $middleware = new ConvertRedirectsForDatastar;

        // Set flash data like redirect()->with() would do
        session()->flash('success', 'Operation completed');

        $response = new RedirectResponse('/dashboard');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        // Verify reflash was called (flash data moved from old to new)
        $this->assertNotNull(session()->get('_flash.new'));
        $this->assertContains('success', session()->get('_flash.new'));
    }

    /**
     * @test
     */
    public function it_attaches_session_cookie_to_response()
    {
        $middleware = new ConvertRedirectsForDatastar;

        session()->flash('test', 'value');

        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $cookies = $result->headers->getCookies();

        $this->assertNotEmpty($cookies);

        $sessionCookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === config('session.cookie');
        });

        $this->assertNotNull($sessionCookie);
        $this->assertEquals(session()->getId(), $sessionCookie->getValue());
    }

    /**
     * @test
     */
    public function it_copies_cookies_from_original_response()
    {
        $middleware = new ConvertRedirectsForDatastar;

        $response = new RedirectResponse('/target');
        $response->headers->setCookie(cookie('custom_cookie', 'test_value'));

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $cookies = $result->headers->getCookies();

        $customCookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === 'custom_cookie';
        });

        $this->assertNotNull($customCookie);
        $this->assertEquals('test_value', $customCookie->getValue());
    }

    /**
     * @test
     */
    public function it_creates_session_cookie_with_correct_configuration()
    {
        config(['session.cookie' => 'test_session']);
        config(['session.lifetime' => 240]);
        config(['session.path' => '/app']);
        config(['session.domain' => 'example.com']);
        config(['session.secure' => true]);
        config(['session.http_only' => true]);
        config(['session.same_site' => 'strict']);

        $middleware = new ConvertRedirectsForDatastar;

        $response = new RedirectResponse('/target');

        $result = $middleware->handle(request(), function () use ($response) {
            return $response;
        });

        $cookies = $result->headers->getCookies();

        $sessionCookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === 'test_session';
        });

        $this->assertNotNull($sessionCookie);
        $this->assertEquals('/app', $sessionCookie->getPath());
        $this->assertEquals('example.com', $sessionCookie->getDomain());
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue($sessionCookie->isHttpOnly());
        $this->assertEquals('strict', $sessionCookie->getSameSite());
    }
}
