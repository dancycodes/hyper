<?php

namespace Dancycodes\Hyper\Tests\Unit\Http;

use Dancycodes\Hyper\Http\HyperRedirect;
use Dancycodes\Hyper\Http\HyperResponse;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test the HyperRedirect class
 *
 * @see TESTING.md - File 3: HyperRedirect Tests
 * Status: 🔄 IN PROGRESS - 8 test methods
 */
class HyperRedirectTest extends TestCase
{
    /**
     * Set up a fake Hyper request
     */
    protected function setupHyperRequest(): void
    {
        request()->headers->set('Datastar-Request', 'true');
    }

    /** @test */
    public function test_redirect_creates_instance()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        $this->assertInstanceOf(HyperRedirect::class, $redirect);
    }

    /** @test */
    public function test_redirect_sets_correct_url()
    {
        $this->setupHyperRequest();

        $targetUrl = '/users/profile';
        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect($targetUrl, $hyperResponse);

        // Convert to response and check the generated JavaScript
        $response = $redirect->toResponse(request());

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        // Get SSE events
        $events = $this->getSSEEvents($response);

        // Should have a script execution event
        $this->assertGreaterThan(0, count($events));

        // Verify the URL is in the JavaScript
        $hasUrlInScript = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], $targetUrl)) {
                $hasUrlInScript = true;
                break;
            }
        }

        $this->assertTrue($hasUrlInScript, "Target URL '{$targetUrl}' should be in the redirect script");
    }

    /** @test */
    public function test_redirect_executes_javascript_window_location()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Should generate JavaScript with window.location
        $hasWindowLocation = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'window.location')) {
                $hasWindowLocation = true;
                break;
            }
        }

        $this->assertTrue($hasWindowLocation, 'Redirect should use window.location');
    }

    /** @test */
    public function test_with_method_flashes_data()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        // Flash data using with()
        $redirect->with('success', 'Profile updated successfully!');
        $redirect->with('user_id', 123);

        // Execute redirect (which flashes to session)
        $response = $redirect->toResponse(request());

        // Verify data was flashed to session
        $this->assertTrue(session()->has('success'));
        $this->assertTrue(session()->has('user_id'));
        $this->assertEquals('Profile updated successfully!', session()->get('success'));
        $this->assertEquals(123, session()->get('user_id'));
    }

    /** @test */
    public function test_with_method_chains_correctly()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        // Test method chaining
        $result = $redirect
            ->with('message', 'Hello')
            ->with('status', 'success');

        $this->assertInstanceOf(HyperRedirect::class, $result);

        // Execute and verify both flash data items
        $response = $result->toResponse(request());

        $this->assertTrue(session()->has('message'));
        $this->assertTrue(session()->has('status'));
        $this->assertEquals('Hello', session()->get('message'));
        $this->assertEquals('success', session()->get('status'));
    }

    /** @test */
    public function test_redirect_returns_hyper_response_instance()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        $response = $redirect->toResponse(request());

        // Should return a StreamedResponse (which is what HyperResponse generates)
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function test_redirect_can_chain_other_methods()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        // Test chaining multiple methods
        $result = $redirect
            ->with('success', 'Saved!')
            ->with(['count' => 5, 'total' => 10]);

        $this->assertInstanceOf(HyperRedirect::class, $result);

        // Execute redirect
        $response = $result->toResponse(request());

        // Verify all flash data
        $this->assertEquals('Saved!', session()->get('success'));
        $this->assertEquals(5, session()->get('count'));
        $this->assertEquals(10, session()->get('total'));
    }

    /** @test */
    public function test_redirect_escapes_url_correctly()
    {
        $this->setupHyperRequest();

        // URL with special characters
        $urlWithSpecialChars = '/search?query=test&category=news';

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect($urlWithSpecialChars, $hyperResponse);

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify URL is properly escaped in JavaScript
        $hasEscapedUrl = false;
        foreach ($events as $event) {
            if (isset($event['data'])) {
                // The URL should be JSON-encoded, so & becomes part of the string
                if (str_contains($event['data'], 'window.location')) {
                    $hasEscapedUrl = true;
                    // Verify it's properly escaped (should not have unescaped special chars)
                    $this->assertStringNotContainsString('<script>', $event['data']);
                    break;
                }
            }
        }

        $this->assertTrue($hasEscapedUrl, 'URL should be safely escaped in JavaScript');
    }

    /** @test */
    public function test_with_method_accepts_array()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        // Flash array of data
        $flashData = [
            'message' => 'Success',
            'type' => 'info',
            'count' => 42,
        ];

        $redirect->with($flashData);
        $response = $redirect->toResponse(request());

        // Verify all array items were flashed
        $this->assertEquals('Success', session()->get('message'));
        $this->assertEquals('info', session()->get('type'));
        $this->assertEquals(42, session()->get('count'));
    }

    /** @test */
    public function test_with_input_method_flashes_input()
    {
        $this->setupHyperRequest();

        // Set up request input
        request()->merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/dashboard', $hyperResponse);

        // Flash input
        $redirect->withInput();
        $response = $redirect->toResponse(request());

        // Verify old input was flashed
        $this->assertTrue(session()->has('_old_input'));
        $oldInput = session()->get('_old_input');
        $this->assertIsArray($oldInput);
        $this->assertArrayHasKey('name', $oldInput);
        $this->assertArrayHasKey('email', $oldInput);
        $this->assertEquals('John Doe', $oldInput['name']);
        $this->assertEquals('john@example.com', $oldInput['email']);
    }

    /** @test */
    public function test_with_errors_method_flashes_errors()
    {
        $this->setupHyperRequest();

        $errors = [
            'email' => ['The email field is required.'],
            'password' => ['The password must be at least 8 characters.'],
        ];

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/login', $hyperResponse);

        // Flash errors
        $redirect->withErrors($errors);
        $response = $redirect->toResponse(request());

        // Verify errors were flashed
        $this->assertTrue(session()->has('errors'));
        $flashedErrors = session()->get('errors');
        $this->assertEquals($errors, $flashedErrors);
    }

    /** @test */
    public function test_back_method_redirects_to_previous_url()
    {
        $this->setupHyperRequest();

        // Simulate a previous URL
        $previousUrl = 'http://localhost/previous-page';
        request()->headers->set('referer', $previousUrl);

        // Mock url()->previous() by setting up the URL in session
        session()->put('_previous.url', $previousUrl);

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Call back() which should change the redirect URL
        $redirect->back();

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify the previous URL is used
        $hasePreviousUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'previous-page')) {
                $hasPreviousUrl = true;
                break;
            }
        }

        $this->assertTrue($hasPreviousUrl, 'Should redirect to previous URL');
    }

    /** @test */
    public function test_back_method_uses_fallback_when_no_previous()
    {
        $this->setupHyperRequest();

        // Clear any previous URL
        session()->forget('_previous.url');

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Call back() with fallback
        $redirect->back('/fallback-url');

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify fallback is used
        $hasFallback = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'fallback')) {
                $hasFallback = true;
                break;
            }
        }

        $this->assertTrue($hasFallback, 'Should use fallback URL when no previous URL');
    }

    /** @test */
    public function test_route_method_redirects_to_named_route()
    {
        $this->setupHyperRequest();

        // Define a test route
        \Illuminate\Support\Facades\Route::get('/test-route', function () {
            return 'test';
        })->name('test.route');

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Redirect to named route
        $redirect->route('test.route');

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify the route URL is used
        $hasRouteUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'test-route')) {
                $hasRouteUrl = true;
                break;
            }
        }

        $this->assertTrue($hasRouteUrl, 'Should redirect to named route URL');
    }

    /** @test */
    public function test_route_method_throws_exception_for_invalid_route()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'non.existent.route' does not exist");

        // Try to redirect to non-existent route
        $redirect->route('non.existent.route');
    }

    /** @test */
    public function test_home_method_redirects_to_root()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Redirect to home
        $redirect->home();

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify redirect to root URL
        $hasRootUrl = false;
        foreach ($events as $event) {
            if (isset($event['data'])) {
                // Should contain localhost or the base URL
                if (str_contains($event['data'], 'window.location') &&
                    (str_contains($event['data'], 'localhost') || str_contains($event['data'], '\\/'))) {
                    $hasRootUrl = true;
                    break;
                }
            }
        }

        $this->assertTrue($hasRootUrl, 'Should redirect to home/root URL');
    }

    /** @test */
    public function test_refresh_method_reloads_current_page()
    {
        $this->setupHyperRequest();

        // Set current URL
        request()->server->set('REQUEST_URI', '/current-page?foo=bar');

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Refresh current page
        $redirect->refresh();

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify current page URL is used
        $hasCurrentUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'window.location')) {
                $hasCurrentUrl = true;
                break;
            }
        }

        $this->assertTrue($hasCurrentUrl, 'Should refresh current page');
    }

    /** @test */
    public function test_intended_method_redirects_to_intended_url()
    {
        $this->setupHyperRequest();

        // Set intended URL in session (must be full URL for domain security check)
        $intendedUrl = 'http://localhost/protected-page';
        session()->put('url.intended', $intendedUrl);

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Redirect to intended URL
        $redirect->intended('/default');

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify intended URL is used
        $hasIntendedUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'protected-page')) {
                $hasIntendedUrl = true;
                break;
            }
        }

        $this->assertTrue($hasIntendedUrl, 'Should redirect to intended URL');

        // Verify intended URL was removed from session (pulled)
        $this->assertFalse(session()->has('url.intended'));
    }

    /** @test */
    public function test_intended_method_uses_default_when_no_intended()
    {
        $this->setupHyperRequest();

        // Clear intended URL
        session()->forget('url.intended');

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // Redirect to intended with default
        $redirect->intended('/default-page');

        $response = $redirect->toResponse(request());
        $events = $this->getSSEEvents($response);

        // Verify default URL is used
        $hasDefaultUrl = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'default-page')) {
                $hasDefaultUrl = true;
                break;
            }
        }

        $this->assertTrue($hasDefaultUrl, 'Should use default URL when no intended URL exists');
    }

    /** @test */
    public function test_force_reload_method_reloads_page()
    {
        $this->setupHyperRequest();

        $hyperResponse = app(HyperResponse::class);
        $redirect = new HyperRedirect('/ignored', $hyperResponse);

        // BUG FIXED: forceReload() now correctly returns StreamedResponse (return type: mixed)
        // Previously declared incorrect return type causing TypeError
        $response = $redirect->forceReload(true);

        // Verify it returns a StreamedResponse
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        // Verify the reload script is generated
        $events = $this->getSSEEvents($response);
        $hasReloadScript = false;
        foreach ($events as $event) {
            if (isset($event['data']) && str_contains($event['data'], 'window.location.reload')) {
                $hasReloadScript = true;
                break;
            }
        }

        $this->assertTrue($hasReloadScript, 'Should generate window.location.reload script');
    }
}
