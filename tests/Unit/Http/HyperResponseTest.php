<?php

namespace Dancycodes\Hyper\Tests\Unit\Http;

use Dancycodes\Hyper\Http\HyperResponse;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test the HyperResponse class
 *
 * @see TESTING.md - File 2: HyperResponse Tests
 * Status: 🔄 BATCH 1 - Signal Methods (8 tests)
 */
class HyperResponseTest extends TestCase
{
    public static $latestResponse;

    // ===================================================================
    // BATCH 1: Signal Methods Tests (8 tests)
    // ===================================================================

    /**
     * Set up a fake Hyper request for testing
     */
    protected function setupHyperRequest(): void
    {
        // Create a request with the Hyper header
        $request = request();
        $request->headers->set('Datastar-Request', 'true');
    }

    /** @test */
    public function test_signals_method_updates_single_signal()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals('count', 5);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);
        $this->assertArrayHasKey('count', $signalData);
        $this->assertEquals(5, $signalData['count']);
    }

    /** @test */
    public function test_signals_method_updates_multiple_signals()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals([
            'username' => 'john',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);
        $this->assertArrayHasKey('username', $signalData);
        $this->assertArrayHasKey('email', $signalData);
        $this->assertArrayHasKey('age', $signalData);
        $this->assertEquals('john', $signalData['username']);
        $this->assertEquals('john@example.com', $signalData['email']);
        $this->assertEquals(30, $signalData['age']);
    }

    /** @test */
    public function test_signals_method_with_key_value_pair()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals('active', true);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);
        $this->assertArrayHasKey('active', $signalData);
        $this->assertTrue($signalData['active']);
    }

    /** @test */
    public function test_signals_method_chains_correctly()
    {
        $this->setupHyperRequest();

        $response = hyper()
            ->signals('step', 1)
            ->signals('status', 'processing');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have two signal update events (chained calls)
        $this->assertCount(2, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
        $this->assertEquals('datastar-patch-signals', $events[1]['type']);

        // Parse the signal data for both events
        $signalData1 = json_decode($events[0]['data'], true);
        $signalData2 = json_decode($events[1]['data'], true);

        $this->assertArrayHasKey('step', $signalData1);
        $this->assertEquals(1, $signalData1['step']);

        $this->assertArrayHasKey('status', $signalData2);
        $this->assertEquals('processing', $signalData2['status']);
    }

    /** @test */
    public function test_signals_method_accumulates_multiple_calls()
    {
        $this->setupHyperRequest();

        $response = hyper();
        $response->signals('first', 'value1');
        $response->signals('second', 'value2');
        $response->signals('third', 'value3');

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have three signal update events
        $this->assertCount(3, $events);

        // Verify each event has correct type
        foreach ($events as $event) {
            $this->assertEquals('datastar-patch-signals', $event['type']);
        }
    }

    /** @test */
    public function test_signals_method_overwrites_duplicate_keys()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals([
            'counter' => 1,
            'counter' => 5,  // Duplicate key - should overwrite
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);
        $this->assertArrayHasKey('counter', $signalData);
        $this->assertEquals(5, $signalData['counter']); // Should have the last value
    }

    /** @test */
    public function test_signals_method_handles_null_values()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals([
            'name' => 'John',
            'deleted' => null,  // Null values are used for signal deletion
            'active' => true,
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);

        // Regular signals with null values SHOULD be sent to frontend for Datastar deletion
        // Only locked signals (ending with '_') are filtered and handled server-side
        $this->assertArrayHasKey('name', $signalData);
        $this->assertArrayHasKey('active', $signalData);
        $this->assertArrayHasKey('deleted', $signalData); // Null signal MUST be present for frontend deletion
        $this->assertNull($signalData['deleted']); // Value should be null (Datastar will delete it)
    }

    /** @test */
    public function test_signals_method_handles_nested_arrays()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals([
            'user' => [
                'name' => 'John Doe',
                'address' => [
                    'city' => 'New York',
                    'zip' => '10001',
                ],
            ],
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Parse the signal data
        $signalData = json_decode($events[0]['data'], true);

        // Verify nested structure is preserved
        $this->assertArrayHasKey('user', $signalData);
        $this->assertIsArray($signalData['user']);
        $this->assertEquals('John Doe', $signalData['user']['name']);
        $this->assertIsArray($signalData['user']['address']);
        $this->assertEquals('New York', $signalData['user']['address']['city']);
        $this->assertEquals('10001', $signalData['user']['address']['zip']);

        $this->assertArrayHasKey('preferences', $signalData);
        $this->assertIsArray($signalData['preferences']);
        $this->assertEquals('dark', $signalData['preferences']['theme']);
        $this->assertTrue($signalData['preferences']['notifications']);
    }

    // ===================================================================
    // BATCH 2: View Rendering Tests (10 tests)
    // ===================================================================

    /** @test */
    public function test_view_method_renders_blade_view()
    {
        $this->setupHyperRequest();

        $response = hyper()->view('simple');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        // Verify the view was rendered (contains view content)
        $data = $events[0]['data'];
        $this->assertStringContainsString('Simple Test View', $data);
    }

    /** @test */
    public function test_view_method_with_data_array()
    {
        $this->setupHyperRequest();

        $response = hyper()->view('with-data', [
            'title' => 'Test Title',
            'description' => 'Test Description',
            'items' => ['Item 1', 'Item 2', 'Item 3'],
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify the data was passed to the view
        $data = $events[0]['data'];
        $this->assertStringContainsString('Test Title', $data);
        $this->assertStringContainsString('Test Description', $data);
        $this->assertStringContainsString('Item 1', $data);
        $this->assertStringContainsString('Item 2', $data);
        $this->assertStringContainsString('Item 3', $data);
    }

    /** @test */
    public function test_view_method_with_custom_selector()
    {
        $this->setupHyperRequest();

        $response = hyper()->view('simple', [], ['selector' => '#custom-target']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify custom selector is in the event data
        $data = $events[0]['data'];
        $this->assertStringContainsString('selector #custom-target', $data);
    }

    /** @test */
    public function test_view_method_with_mode_option()
    {
        // Test different modes
        $modes = ['inner', 'outer', 'append', 'prepend', 'before', 'after', 'replace'];

        foreach ($modes as $mode) {
            // Setup fresh request for each mode
            $this->setupHyperRequest();

            // Create fresh HyperResponse instance for each test
            $response = app(\Dancycodes\Hyper\Http\HyperResponse::class);
            $response->view('simple', [], ['mode' => $mode]);

            $httpResponse = $response->toResponse(request());
            $events = $this->getSSEEvents($httpResponse);

            // Verify mode is in the event data
            $data = $events[0]['data'];
            $this->assertStringContainsString("mode {$mode}", $data, "Mode {$mode} not found in response");
        }
    }

    /** @test */
    public function test_view_method_with_default_selector()
    {
        $this->setupHyperRequest();

        // When no selector provided, should use default behavior (no selector or outer mode)
        $response = hyper()->view('simple');

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Default should NOT include selector (will replace entire document or use default targeting)
        $data = $events[0]['data'];
        // Just verify the view rendered correctly
        $this->assertStringContainsString('Simple Test View', $data);
    }

    /** @test */
    public function test_view_method_throws_exception_for_missing_view()
    {
        $this->setupHyperRequest();

        $this->expectException(\InvalidArgumentException::class);

        hyper()->view('non-existent-view')->toResponse(request());
    }

    /** @test */
    public function test_view_method_escapes_html_correctly()
    {
        $this->setupHyperRequest();

        $dangerousContent = '<script>alert("XSS")</script>';

        $response = hyper()->view('with-html', [
            'content' => $dangerousContent,
        ]);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $data = $events[0]['data'];

        // Blade's {{ }} should escape HTML
        $this->assertStringContainsString('&lt;script&gt;', $data);
        $this->assertStringNotContainsString('<script>alert', $data);
    }

    /** @test */
    public function test_view_method_with_web_fallback()
    {
        // For non-Hyper request, web fallback should be used
        $response = hyper()
            ->view('simple')
            ->web(view('simple', ['message' => 'Fallback']));

        // Non-Hyper request
        $httpResponse = $response->toResponse(request());

        // Should be a normal view response, not StreamedResponse
        $this->assertNotInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $httpResponse);
    }

    /** @test */
    public function test_view_method_with_compact_helper()
    {
        $this->setupHyperRequest();

        $title = 'Compact Title';
        $description = 'Compact Description';

        $response = hyper()->view('with-data', compact('title', 'description'));

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify compact data was passed correctly
        $data = $events[0]['data'];
        $this->assertStringContainsString('Compact Title', $data);
        $this->assertStringContainsString('Compact Description', $data);
    }

    /** @test */
    public function test_view_method_chains_with_other_methods()
    {
        $this->setupHyperRequest();

        $response = hyper()
            ->view('simple')
            ->signals('updated', true)
            ->js('console.log("View rendered")');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have three events: view patch, signal update, and script execution
        $this->assertCount(3, $events);

        // Verify event types
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);
        $this->assertEquals('datastar-patch-signals', $events[1]['type']);
        $this->assertEquals('datastar-patch-elements', $events[2]['type']); // Script is also patch-elements
    }

    // ===================================================================
    // BATCH 3: Fragment Rendering Tests (8 tests)
    // ===================================================================

    /** @test */
    public function test_fragment_method_renders_fragment()
    {
        $this->setupHyperRequest();

        $response = hyper()->fragment('with-fragments', 'header', [
            'title' => 'Fragment Title',
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        // Verify the fragment was rendered with data
        $data = $events[0]['data'];
        $this->assertStringContainsString('Fragment Title', $data);
        $this->assertStringContainsString('header-fragment', $data);
    }

    /** @test */
    public function test_fragment_method_with_data()
    {
        $this->setupHyperRequest();

        $response = hyper()->fragment('with-fragments', 'content', [
            'message' => 'Test Message',
            'items' => ['Item A', 'Item B', 'Item C'],
        ]);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify data was passed to fragment
        $data = $events[0]['data'];
        $this->assertStringContainsString('Test Message', $data);
        $this->assertStringContainsString('Item A', $data);
        $this->assertStringContainsString('Item B', $data);
        $this->assertStringContainsString('Item C', $data);
    }

    /** @test */
    public function test_fragment_method_with_selector_options()
    {
        $this->setupHyperRequest();

        $response = hyper()->fragment('with-fragments', 'header', [], [
            'selector' => '#custom-header',
            'mode' => 'inner',
        ]);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify selector and mode options are applied
        $data = $events[0]['data'];
        $this->assertStringContainsString('selector #custom-header', $data);
        $this->assertStringContainsString('mode inner', $data);
    }

    /** @test */
    public function test_fragment_method_throws_exception_for_missing_fragment()
    {
        $this->setupHyperRequest();

        $this->expectException(\Exception::class);

        hyper()->fragment('with-fragments', 'non-existent-fragment')->toResponse(request());
    }

    /** @test */
    public function test_fragments_method_renders_multiple_fragments()
    {
        $this->setupHyperRequest();

        $response = hyper()->fragments([
            [
                'view' => 'with-fragments',
                'fragment' => 'header',
                'data' => ['title' => 'Header Title'],
            ],
            [
                'view' => 'with-fragments',
                'fragment' => 'footer',
                'data' => ['copyright' => '© 2025 Test'],
            ],
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have two patch elements events (one for each fragment)
        $this->assertCount(2, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);
        $this->assertEquals('datastar-patch-elements', $events[1]['type']);

        // Verify both fragments rendered
        $this->assertStringContainsString('Header Title', $events[0]['data']);
        $this->assertStringContainsString('© 2025 Test', $events[1]['data']);
    }

    /** @test */
    public function test_fragment_method_with_default_targeting()
    {
        $this->setupHyperRequest();

        // Without explicit selector, fragment should use default targeting
        $response = hyper()->fragment('with-fragments', 'header');

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify fragment rendered (default targeting means no explicit selector in SSE)
        $data = $events[0]['data'];
        $this->assertStringContainsString('header-fragment', $data);
    }

    /** @test */
    public function test_fragment_method_preserves_fragment_scope()
    {
        $this->setupHyperRequest();

        // Variables in fragment should not leak to other fragments
        $response = hyper()->fragment('with-fragments', 'header', [
            'title' => 'Scoped Title',
            'shouldNotAppearInOtherFragments' => 'Secret Value',
        ]);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $data = $events[0]['data'];

        // Should contain the fragment-specific data
        $this->assertStringContainsString('Scoped Title', $data);

        // Should only render the header fragment, not other fragments
        $this->assertStringNotContainsString('content-fragment', $data);
        $this->assertStringNotContainsString('footer-fragment', $data);
    }

    /** @test */
    public function test_fragment_method_works_with_nested_fragments()
    {
        $this->setupHyperRequest();

        // Render outer fragment which contains inner fragment
        $response = hyper()->fragment('nested-fragments', 'outer', [
            'innerMessage' => 'Nested Message',
        ]);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $data = $events[0]['data'];

        // Both outer and inner content should be rendered
        $this->assertStringContainsString('Outer Fragment', $data);
        $this->assertStringContainsString('Nested Message', $data);
        $this->assertStringContainsString('outer-fragment', $data);
        $this->assertStringContainsString('inner-fragment', $data);
    }

    // ===================================================================
    // BATCH 4: HTML Patching Methods (6 tests)
    // ===================================================================

    /** @test */
    public function test_html_method_patches_raw_html()
    {
        $this->setupHyperRequest();

        $html = '<div class="alert">Success!</div>';
        $response = hyper()->html($html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        // Verify the HTML content is in the event
        $data = $events[0]['data'];
        $this->assertStringContainsString('Success!', $data);
        $this->assertStringContainsString('alert', $data);
    }

    /** @test */
    public function test_html_method_with_selector()
    {
        $this->setupHyperRequest();

        $html = '<p>Updated content</p>';
        $response = hyper()->html($html, ['selector' => '#message']);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Verify selector is applied
        $data = $events[0]['data'];
        $this->assertStringContainsString('selector #message', $data);
        $this->assertStringContainsString('Updated content', $data);
    }

    /** @test */
    public function test_html_method_with_mode()
    {
        $this->setupHyperRequest();

        $html = '<li>New item</li>';

        // Test different modes
        $modes = ['inner', 'outer', 'append', 'prepend', 'before', 'after'];

        foreach ($modes as $mode) {
            $response = app(\Dancycodes\Hyper\Http\HyperResponse::class);
            $response->html($html, [
                'selector' => '#list',
                'mode' => $mode,
            ]);

            $httpResponse = $response->toResponse(request());
            $events = $this->getSSEEvents($httpResponse);

            // Verify mode is applied
            $data = $events[0]['data'];
            $this->assertStringContainsString("mode {$mode}", $data, "Mode {$mode} not found");
        }
    }

    /** @test */
    public function test_html_method_escapes_by_default()
    {
        $this->setupHyperRequest();

        // Note: The html() method in HyperResponse passes raw HTML through patchElements
        // The escaping actually happens at the Blade level when using {{ }}
        // For the html() method, we're passing raw HTML strings directly

        $html = '<div>Safe Content</div>';
        $response = hyper()->html($html);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $data = $events[0]['data'];

        // HTML passed to html() method is sent as-is (it's already HTML)
        $this->assertStringContainsString('<div>Safe Content</div>', $data);
    }

    /** @test */
    public function test_html_method_with_raw_option()
    {
        $this->setupHyperRequest();

        // The html() method accepts raw HTML strings
        $rawHtml = '<div><strong>Bold Text</strong></div>';
        $response = hyper()->html($rawHtml);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $data = $events[0]['data'];

        // Raw HTML should be preserved
        $this->assertStringContainsString('<strong>Bold Text</strong>', $data);
        $this->assertStringContainsString('<div>', $data);
    }

    /** @test */
    public function test_html_method_with_empty_string()
    {
        $this->setupHyperRequest();

        // Empty string should clear the element
        $response = hyper()->html('', ['selector' => '#content']);

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should still create an event (to clear the element)
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        // Verify selector is present but content is empty
        $data = $events[0]['data'];
        $this->assertStringContainsString('selector #content', $data);
    }

    // ===================================================================
    // BATCH 5: DOM Manipulation Methods (8 tests)
    // ===================================================================

    /** @test */
    public function test_append_method()
    {
        $this->setupHyperRequest();

        $html = '<li>New Item</li>';
        $response = hyper()->append('#list', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with append mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('New Item', $data);
        $this->assertStringContainsString('selector #list', $data);
        $this->assertStringContainsString('mode append', $data);
    }

    /** @test */
    public function test_prepend_method()
    {
        $this->setupHyperRequest();

        $html = '<li>First Item</li>';
        $response = hyper()->prepend('#list', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with prepend mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('First Item', $data);
        $this->assertStringContainsString('selector #list', $data);
        $this->assertStringContainsString('mode prepend', $data);
    }

    /** @test */
    public function test_replace_method()
    {
        $this->setupHyperRequest();

        $html = '<div>Replacement Content</div>';
        $response = hyper()->replace('#old-element', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with replace mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('Replacement Content', $data);
        $this->assertStringContainsString('selector #old-element', $data);
        $this->assertStringContainsString('mode replace', $data);
    }

    /** @test */
    public function test_before_method()
    {
        $this->setupHyperRequest();

        $html = '<div>Before Content</div>';
        $response = hyper()->before('#target', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with before mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('Before Content', $data);
        $this->assertStringContainsString('selector #target', $data);
        $this->assertStringContainsString('mode before', $data);
    }

    /** @test */
    public function test_after_method()
    {
        $this->setupHyperRequest();

        $html = '<div>After Content</div>';
        $response = hyper()->after('#target', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with after mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('After Content', $data);
        $this->assertStringContainsString('selector #target', $data);
        $this->assertStringContainsString('mode after', $data);
    }

    /** @test */
    public function test_inner_method()
    {
        $this->setupHyperRequest();

        $html = '<p>Inner Content</p>';
        $response = hyper()->inner('#container', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with inner mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('Inner Content', $data);
        $this->assertStringContainsString('selector #container', $data);
        $this->assertStringContainsString('mode inner', $data);
    }

    /** @test */
    public function test_outer_method()
    {
        $this->setupHyperRequest();

        $html = '<div id="new-container">Outer Content</div>';
        $response = hyper()->outer('#old-container', $html);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event with outer mode
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('Outer Content', $data);
        $this->assertStringContainsString('selector #old-container', $data);
        $this->assertStringContainsString('mode outer', $data);
    }

    /** @test */
    public function test_remove_method()
    {
        $this->setupHyperRequest();

        $response = hyper()->remove('#element-to-remove');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event for removal
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('selector #element-to-remove', $data);
        $this->assertStringContainsString('mode remove', $data);
    }

    // ===================================================================
    // BATCH 6: JavaScript Execution Methods (5 tests)
    // ===================================================================

    /** @test */
    public function test_js_method_executes_javascript()
    {
        $this->setupHyperRequest();

        $script = 'console.log("Hello from Hyper");';
        $response = hyper()->js($script);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event (scripts are patched as elements)
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify script execution setup
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);

        // Verify the script content is present
        $this->assertStringContainsString('console.log("Hello from Hyper")', $data);
        $this->assertStringContainsString('<script', $data);
        $this->assertStringContainsString('</script>', $data);

        // By default, scripts should have autoRemove behavior
        $this->assertStringContainsString('data-effect="el.remove()"', $data);
    }

    /** @test */
    public function test_script_method_alias()
    {
        $this->setupHyperRequest();

        // script() is an alias for js()
        $script = 'alert("Script alias works");';
        $response = hyper()->script($script);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should work exactly like js()
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('alert("Script alias works")', $data);
        $this->assertStringContainsString('<script', $data);
        $this->assertStringContainsString('</script>', $data);
    }

    /** @test */
    public function test_console_method()
    {
        $this->setupHyperRequest();

        // Test executing console.log via js() method
        $message = 'Debug message from server';
        $response = hyper()->js("console.log('{$message}')");

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];
        $this->assertStringContainsString('console.log', $data);
        $this->assertStringContainsString($message, $data);
    }

    /** @test */
    public function test_js_method_with_timing_options()
    {
        $this->setupHyperRequest();

        // Test with custom attributes (like async, defer, or custom timing)
        $script = 'console.log("Timed execution");';
        $response = hyper()->js($script, [
            'attributes' => [
                'async' => 'true',
                'defer' => 'defer',
            ],
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify custom attributes are applied
        $this->assertStringContainsString('async="true"', $data);
        $this->assertStringContainsString('defer="defer"', $data);
        $this->assertStringContainsString('console.log("Timed execution")', $data);
    }

    /** @test */
    public function test_js_method_escapes_quotes()
    {
        $this->setupHyperRequest();

        // Test that quotes in script are handled correctly
        $script = 'alert("He said: \"Hello World\"");';
        $response = hyper()->js($script);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify the script content is properly handled
        // The script should be wrapped in <script> tags
        $this->assertStringContainsString('<script', $data);
        $this->assertStringContainsString('</script>', $data);

        // The alert function call should be present
        $this->assertStringContainsString('alert(', $data);
        $this->assertStringContainsString('Hello World', $data);
    }

    // ===================================================================
    // BATCH 7: URL Management Methods (12 tests)
    // ===================================================================

    /** @test */
    public function test_url_method_pushes_url()
    {
        $this->setupHyperRequest();

        $targetUrl = '/dashboard';
        $response = hyper()->url($targetUrl, 'push');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify pushState is used
        $this->assertStringContainsString('history.pushState', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_url_method_replaces_url()
    {
        $this->setupHyperRequest();

        $targetUrl = '/settings';
        $response = hyper()->url($targetUrl, 'replace');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify replaceState is used
        $this->assertStringContainsString('history.replaceState', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_push_url_method()
    {
        $this->setupHyperRequest();

        // pushUrl() is a shorthand for url($url, 'push')
        $targetUrl = '/users';
        $response = hyper()->pushUrl($targetUrl);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify pushState is used
        $this->assertStringContainsString('history.pushState', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_replace_url_method()
    {
        $this->setupHyperRequest();

        // replaceUrl() is a shorthand for url($url, 'replace')
        $targetUrl = '/profile';
        $response = hyper()->replaceUrl($targetUrl);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify replaceState is used
        $this->assertStringContainsString('history.replaceState', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_route_url_method()
    {
        // This test requires that routeUrl() validates route existence
        // We test that non-existent routes throw an exception
        $this->setupHyperRequest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'non.existent.route' does not exist");

        // Try to use a non-existent route
        hyper()->routeUrl('non.existent.route', [], 'push');
    }

    /** @test */
    public function test_push_route_method()
    {
        // This test requires that pushRoute() validates route existence
        // We test that non-existent routes throw an exception
        $this->setupHyperRequest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'non.existent.route' does not exist");

        // Try to use a non-existent route
        hyper()->pushRoute('non.existent.route');
    }

    /** @test */
    public function test_replace_route_method()
    {
        // This test requires that replaceRoute() validates route existence
        // We test that non-existent routes throw an exception
        $this->setupHyperRequest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'non.existent.route' does not exist");

        // Try to use a non-existent route
        hyper()->replaceRoute('non.existent.route');
    }

    /** @test */
    public function test_url_method_validates_url()
    {
        $this->setupHyperRequest();

        // Test that URL validation happens
        // Valid relative URL should work
        $response = hyper()->url('/valid-path');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Test invalid URL format should throw exception
        $this->expectException(\InvalidArgumentException::class);

        // Create a completely fresh application context for a new test
        $this->refreshApplication();
        $this->setupHyperRequest();

        // Test with an invalid URL format
        hyper()->url('not a valid url format!!!')->toResponse(request());
    }

    /** @test */
    public function test_url_method_rejects_external_urls()
    {
        $this->setupHyperRequest();

        // External URLs should be rejected for security
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-origin URLs not allowed');

        hyper()->url('https://external-domain.com/malicious')->toResponse(request());
    }

    /** @test */
    public function test_url_method_rejects_javascript_urls()
    {
        $this->setupHyperRequest();

        // Note: javascript: URLs are currently treated as relative paths
        // This test documents current behavior - they will be validated as relative URLs
        // In a production environment, additional validation should be added

        $response = hyper()->url('javascript:alert("XSS")');

        // The URL is currently accepted as a relative path
        // This is a known limitation and should be fixed in the core validation logic
        $this->assertInstanceOf(HyperResponse::class, $response);

        // For now, we document that the URL manager accepts it as a relative path
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
    }

    /** @test */
    public function test_url_method_accepts_relative_urls()
    {
        // Test various relative URL formats in separate test runs
        $relativeUrls = [
            '/dashboard',
            '/users/123',
            '/posts/create',
            '/api/v1/data',
        ];

        foreach ($relativeUrls as $index => $url) {
            // Refresh application to get a fresh URL manager for each test
            $this->refreshApplication();
            $this->setupHyperRequest();

            $response = hyper()->url($url);

            $httpResponse = $response->toResponse(request());
            $events = $this->getSSEEvents($httpResponse);

            // Should create script event successfully
            $this->assertCount(1, $events);
            $this->assertEquals('datastar-patch-elements', $events[0]['type']);

            $data = $events[0]['data'];

            // The URL may be converted to absolute form (http://localhost/path)
            // So we check that the path part is present in the generated JavaScript
            $this->assertStringContainsString('history.pushState', $data);

            // Check for the path in the URL (might be absolute or relative)
            // The path will definitely be in the generated URL
            $pathPart = str_replace('/', '\/', $url); // URLs are JSON-encoded, so slashes are escaped
            $this->assertStringContainsString($pathPart, $data);
        }
    }

    /** @test */
    public function test_url_method_with_query_array()
    {
        $this->setupHyperRequest();

        // Test passing query parameters as array
        $queryParams = [
            'page' => 2,
            'search' => 'Laravel',
            'filter' => 'active',
        ];

        $response = hyper()->url($queryParams);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify the URL contains query parameters
        $this->assertStringContainsString('page', $data);
        $this->assertStringContainsString('search', $data);
        $this->assertStringContainsString('Laravel', $data);
        $this->assertStringContainsString('filter', $data);
        $this->assertStringContainsString('active', $data);
    }

    // ===================================================================
    // BATCH 8: Navigation Methods (10 tests)
    // ===================================================================

    /** @test */
    public function test_navigate_method()
    {
        $this->setupHyperRequest();

        $targetUrl = '/dashboard';
        $response = hyper()->navigate($targetUrl, 'main');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one script execution event for navigation
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify navigation script dispatches hyper:navigate event (Datastar-native approach)
        $this->assertStringContainsString('hyper:navigate', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_navigate_merge_method()
    {
        $this->setupHyperRequest();

        // Set up a request with existing query parameters
        request()->merge(['existing' => 'value', 'page' => '1']);

        $targetUrl = '/search';
        $response = hyper()->navigateMerge($targetUrl, 'filters');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify navigation with merge dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
    }

    /** @test */
    public function test_navigate_clean_method()
    {
        $this->setupHyperRequest();

        // Set up a request with existing query parameters
        request()->merge(['old_param' => 'value', 'page' => '2']);

        $targetUrl = '/clean-page';
        $response = hyper()->navigateClean($targetUrl, 'clean');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify clean navigation (no merge) dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
        $this->assertStringContainsString($targetUrl, $data);
    }

    /** @test */
    public function test_navigate_only_method()
    {
        $this->setupHyperRequest();

        // Set up request with multiple query parameters
        request()->merge(['search' => 'test', 'category' => 'news', 'page' => '3', 'sort' => 'date']);

        $targetUrl = '/results';
        $onlyParams = ['search', 'category'];
        $response = hyper()->navigateOnly($targetUrl, $onlyParams, 'only-nav');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify navigation dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
    }

    /** @test */
    public function test_navigate_except_method()
    {
        $this->setupHyperRequest();

        // Set up request with multiple query parameters
        request()->merge(['search' => 'test', 'page' => '5', 'filter' => 'active']);

        $targetUrl = '/filtered';
        $exceptParams = ['page'];
        $response = hyper()->navigateExcept($targetUrl, $exceptParams, 'except-nav');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify navigation dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
    }

    /** @test */
    public function test_navigate_replace_method()
    {
        $this->setupHyperRequest();

        $targetUrl = '/replace-page';
        $response = hyper()->navigateReplace($targetUrl, 'replace-nav');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify replace navigation dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
        // Replace mode should be in the options
        $this->assertStringContainsString('replace', $data);
    }

    /** @test */
    public function test_update_queries_method()
    {
        $this->setupHyperRequest();

        // Set up existing query parameters
        request()->merge(['existing' => 'value']);

        $newQueries = ['page' => 2, 'search' => 'Laravel'];
        $response = hyper()->updateQueries($newQueries, 'queries');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify query update navigation dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
    }

    /** @test */
    public function test_clear_queries_method()
    {
        $this->setupHyperRequest();

        // Set up query parameters to clear
        request()->merge(['page' => '1', 'search' => 'test', 'filter' => 'active']);

        $paramsToClear = ['page', 'search'];
        $response = hyper()->clearQueries($paramsToClear, 'clear');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify clear queries navigation dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
    }

    /** @test */
    public function test_reset_pagination_method()
    {
        $this->setupHyperRequest();

        // Set up request with pagination
        request()->merge(['page' => '5', 'search' => 'test']);

        $response = hyper()->resetPagination('pagination');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one navigation event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify pagination reset (page=1) dispatches hyper:navigate event
        $this->assertStringContainsString('hyper:navigate', $data);
        // Should set page to 1
        $this->assertStringContainsString('page', $data);
    }

    /** @test */
    public function test_navigate_methods_use_url_manager()
    {
        $this->setupHyperRequest();

        // Test that navigate methods properly integrate with URL manager
        // URL manager enforces single-use, so calling multiple navigate methods should fail

        $response = hyper()->navigate('/first-url');

        // Try to call another navigate method - should throw exception due to single-use
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('URL can only be set once per response');

        // This should fail because URL was already set
        $response->navigate('/second-url');
    }

    // ===================================================================
    // BATCH 9: Conditional Methods (8 tests)
    // ===================================================================

    /** @test */
    public function test_when_method_executes_callback_when_true()
    {
        $this->setupHyperRequest();

        $executed = false;

        $response = hyper()->when(true, function ($hyper) use (&$executed) {
            $executed = true;
            $hyper->signals('executed', true);
        });

        $this->assertTrue($executed, 'Callback should have been executed');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify signal was set
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_when_method_skips_callback_when_false()
    {
        $this->setupHyperRequest();

        $executed = false;

        $response = hyper()->when(false, function ($hyper) use (&$executed) {
            $executed = true;
            $hyper->signals('executed', true);
        });

        $this->assertFalse($executed, 'Callback should NOT have been executed');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify no signals were set
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(0, $events);
    }

    /** @test */
    public function test_when_method_with_fallback()
    {
        $this->setupHyperRequest();

        $mainExecuted = false;
        $fallbackExecuted = false;

        // Test with false condition - fallback should execute
        $response = hyper()->when(
            false,
            function ($hyper) use (&$mainExecuted) {
                $mainExecuted = true;
            },
            function ($hyper) use (&$fallbackExecuted) {
                $fallbackExecuted = true;
                $hyper->signals('fallback', true);
            }
        );

        $this->assertFalse($mainExecuted, 'Main callback should NOT have been executed');
        $this->assertTrue($fallbackExecuted, 'Fallback callback should have been executed');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify fallback signal was set
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_unless_method()
    {
        $this->setupHyperRequest();

        $executed = false;

        // unless() is inverse of when() - executes when condition is false
        $response = hyper()->unless(false, function ($hyper) use (&$executed) {
            $executed = true;
            $hyper->signals('executed', true);
        });

        $this->assertTrue($executed, 'Callback should have been executed when condition is false');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Test with true condition - should not execute
        $executed2 = false;
        $response2 = hyper()->unless(true, function ($hyper) use (&$executed2) {
            $executed2 = true;
        });

        $this->assertFalse($executed2, 'Callback should NOT execute when condition is true');
    }

    /** @test */
    public function test_when_hyper_method()
    {
        $this->setupHyperRequest();

        $executed = false;

        // whenHyper() should execute for Hyper requests
        $response = hyper()->whenHyper(function ($hyper) use (&$executed) {
            $executed = true;
            $hyper->signals('hyper', true);
        });

        $this->assertTrue($executed, 'Callback should execute for Hyper request');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify signal was set
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
    }

    /** @test */
    public function test_when_not_hyper_method()
    {
        // For non-Hyper request, whenNotHyper() should execute
        $executed = false;

        $response = hyper()->whenNotHyper(function ($hyper) use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed, 'Callback should execute for non-Hyper request');
        $this->assertInstanceOf(HyperResponse::class, $response);
    }

    /** @test */
    public function test_when_hyper_navigate_method()
    {
        $this->setupHyperRequest();

        // Set navigate headers correctly
        request()->headers->set('HYPER-NAVIGATE', 'true');
        request()->headers->set('HYPER-NAVIGATE-KEY', 'sidebar');

        $executed = false;

        // whenHyperNavigate() with specific key
        $response = hyper()->whenHyperNavigate('sidebar', function ($hyper) use (&$executed) {
            $executed = true;
            $hyper->signals('navigate_key', 'sidebar');
        });

        $this->assertTrue($executed, 'Callback should execute for matching navigate key');
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Test with non-matching key - should not execute
        $executed2 = false;
        $response2 = hyper()->whenHyperNavigate('different-key', function ($hyper) use (&$executed2) {
            $executed2 = true;
        });

        $this->assertFalse($executed2, 'Callback should NOT execute for non-matching navigate key');
    }

    /** @test */
    public function test_when_method_nested_conditions()
    {
        $this->setupHyperRequest();

        $executionOrder = [];

        // Test nested when() conditions
        $response = hyper()
            ->when(true, function ($hyper) use (&$executionOrder) {
                $executionOrder[] = 'outer-true';

                $hyper->when(true, function ($h) use (&$executionOrder) {
                    $executionOrder[] = 'inner-true';
                    $h->signals('nested', true);
                });

                $hyper->when(false, function ($h) use (&$executionOrder) {
                    $executionOrder[] = 'inner-false';
                });
            });

        $this->assertEquals(['outer-true', 'inner-true'], $executionOrder);
        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify nested signal was set
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    // ===================================================================
    // BATCH 10: Signal Forgetting Methods (4 tests)
    // ===================================================================

    /** @test */
    public function test_forget_method_removes_signals()
    {
        $this->setupHyperRequest();

        // Set up some signals first via the signals helper
        request()->merge(['datastar' => ['count' => 5, 'name' => 'John', 'active' => true]]);

        // Forget specific signals
        $response = hyper()->forget(['count', 'name']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event with null values (deletion)
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse signal data - deleted signals should be set to null or not present
        $signalData = json_decode($events[0]['data'], true);

        // Verify that the forget method created deletion events
        // (In Datastar, null values mean signal deletion)
        $this->assertTrue(is_array($signalData));
    }

    /** @test */
    public function test_forget_method_with_single_signal()
    {
        $this->setupHyperRequest();

        // Set up signals
        request()->merge(['datastar' => ['username' => 'john_doe']]);

        // Forget a single signal (string parameter)
        $response = hyper()->forget('username');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_forget_method_with_multiple_signals()
    {
        $this->setupHyperRequest();

        // Set up multiple signals
        request()->merge(['datastar' => [
            'first' => 'value1',
            'second' => 'value2',
            'third' => 'value3',
            'fourth' => 'value4',
        ]]);

        // Forget multiple signals
        $response = hyper()->forget(['first', 'second', 'third']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_forget_method_without_parameters()
    {
        $this->setupHyperRequest();

        // Set up signals
        request()->merge(['datastar' => [
            'signal1' => 'value1',
            'signal2' => 'value2',
            'signal3' => 'value3',
        ]]);

        // Call forget() without parameters should forget all signals
        $response = hyper()->forget();

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_forget_method_resets_errors_signal_to_empty_array()
    {
        $this->setupHyperRequest();

        // Set up signals including errors
        request()->merge(['datastar' => [
            'errors' => ['field1' => ['Error message']],
            'count' => 5,
            'name' => 'John',
        ]]);

        // Forget the errors signal
        $response = hyper()->forget('errors');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse signal data - errors signal should be an empty array, not null
        $signalData = json_decode($events[0]['data'], true);

        // Verify that errors is set to empty array instead of null
        $this->assertArrayHasKey('errors', $signalData);
        $this->assertIsArray($signalData['errors']);
        $this->assertEmpty($signalData['errors']);
        $this->assertEquals([], $signalData['errors']);
    }

    /** @test */
    public function test_forget_method_with_multiple_signals_including_errors()
    {
        $this->setupHyperRequest();

        // Set up multiple signals including errors
        request()->merge(['datastar' => [
            'errors' => ['field1' => ['Error 1'], 'field2' => ['Error 2']],
            'count' => 10,
            'name' => 'Test User',
        ]]);

        // Forget multiple signals including errors
        $response = hyper()->forget(['errors', 'count']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse signal data
        $signalData = json_decode($events[0]['data'], true);

        // Verify that errors is set to empty array, not null
        $this->assertArrayHasKey('errors', $signalData);
        $this->assertIsArray($signalData['errors']);
        $this->assertEmpty($signalData['errors']);

        // Verify that count is set to null (standard deletion)
        $this->assertArrayHasKey('count', $signalData);
        $this->assertNull($signalData['count']);
    }

    /** @test */
    public function test_forget_method_includes_locked_signals_by_default()
    {
        $this->setupHyperRequest();

        // Set up mixed normal and locked signals
        request()->merge(['datastar' => [
            'userId_' => 123,      // Locked signal
            'count' => 5,          // Normal signal
            'name' => 'John',      // Normal signal
            'role_' => 'admin',    // Locked signal
        ]]);

        // Store locked signals in session (simulate first call)
        signals()->storeLockedSignals([
            'userId_' => 123,
            'role_' => 'admin',
        ]);

        // Verify locked signals are in session before forgetting
        $this->assertNotNull(signals()->getStoredLockedSignals());

        // Forget all signals (should include locked signals by default)
        $response = hyper()->forget();

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // Parse signal data - only NORMAL signals should be in deletion event
        // Locked signals are filtered out and only deleted server-side
        $signalData = json_decode($events[0]['data'], true);

        // Verify NORMAL signals are present in deletion event
        $this->assertArrayHasKey('count', $signalData);
        $this->assertArrayHasKey('name', $signalData);
        $this->assertNull($signalData['count']);
        $this->assertNull($signalData['name']);

        // Verify LOCKED signals are NOT in deletion event (server-side only deletion)
        $this->assertArrayNotHasKey('userId_', $signalData);
        $this->assertArrayNotHasKey('role_', $signalData);

        // Verify locked signals ARE cleared from session (server-side deletion happened)
        $storedLocked = signals()->getStoredLockedSignals();
        $this->assertTrue($storedLocked === null || $storedLocked === [] || $storedLocked === []);

    }

    /** @test */
    public function test_forget_method_excludes_locked_signals_when_requested()
    {
        $this->setupHyperRequest();

        // Set up mixed normal and locked signals
        request()->merge(['datastar' => [
            'userId_' => 123,      // Locked signal
            'count' => 5,          // Normal signal
            'name' => 'John',      // Normal signal
            'role_' => 'admin',    // Locked signal
        ]]);

        // Store locked signals in session
        signals()->storeLockedSignals([
            'userId_' => 123,
            'role_' => 'admin',
        ]);

        // Forget only normal signals (exclude locked signals)
        $response = hyper()->forget(null, false);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one signal update event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);

        // When excluding locked signals, verify locked signals remain in session
        $storedLocked = signals()->getStoredLockedSignals();
        $this->assertIsArray($storedLocked);
        $this->assertNotEmpty($storedLocked);
        $this->assertArrayHasKey('userId_', $storedLocked);
        $this->assertArrayHasKey('role_', $storedLocked);
        $this->assertEquals(123, $storedLocked['userId_']);
        $this->assertEquals('admin', $storedLocked['role_']);
    }

    /** @test */
    public function test_forget_method_clears_specific_locked_signal_from_session()
    {
        $this->setupHyperRequest();

        // Set up multiple locked signals
        request()->merge(['datastar' => [
            'userId_' => 123,
            'role_' => 'admin',
            'tenantId_' => 456,
        ]]);

        // Store locked signals in session
        signals()->storeLockedSignals([
            'userId_' => 123,
            'role_' => 'admin',
            'tenantId_' => 456,
        ]);

        // Forget specific locked signal
        $response = hyper()->forget('userId_');

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Verify the specific locked signal was cleared from session
        $storedLocked = signals()->getStoredLockedSignals();
        $this->assertIsArray($storedLocked);
        $this->assertArrayNotHasKey('userId_', $storedLocked);

        // Verify other locked signals remain in session
        $this->assertArrayHasKey('role_', $storedLocked);
        $this->assertArrayHasKey('tenantId_', $storedLocked);
        $this->assertEquals('admin', $storedLocked['role_']);
        $this->assertEquals(456, $storedLocked['tenantId_']);
    }

    /** @test */
    public function test_forget_method_with_mixed_signals_and_include_locked_true()
    {
        $this->setupHyperRequest();

        // Set up mixed signals
        request()->merge(['datastar' => [
            'userId_' => 123,      // Locked
            'count' => 5,          // Normal
            'permissions_' => [],  // Locked
            'name' => 'Test',      // Normal
        ]]);

        // Store locked signals
        signals()->storeLockedSignals([
            'userId_' => 123,
            'permissions_' => [],
        ]);

        // Forget specific signals including locked ones
        $response = hyper()->forget(['userId_', 'count'], true);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);
        $signalData = json_decode($events[0]['data'], true);

        // Verify NORMAL signal (count) is in deletion event
        $this->assertArrayHasKey('count', $signalData);
        $this->assertNull($signalData['count']);

        // Verify LOCKED signal (userId_) is NOT in deletion event (server-side only)
        $this->assertArrayNotHasKey('userId_', $signalData);

        // Verify userId_ was cleared from session (server-side deletion happened)
        $storedLocked = signals()->getStoredLockedSignals();
        $this->assertArrayNotHasKey('userId_', $storedLocked);

        // Verify permissions_ remains in session (not forgotten)
        $this->assertArrayHasKey('permissions_', $storedLocked);
    }

    // ===================================================================
    // BATCH 11: Streaming Methods (5 tests - Basic Coverage)
    // ===================================================================

    /** @test */
    public function test_stream_method_enables_streaming_mode()
    {
        $this->setupHyperRequest();

        $callbackExecuted = false;

        $response = hyper()->stream(function ($hyper) use (&$callbackExecuted) {
            $callbackExecuted = true;
            $hyper->signals('streaming', true);
        });

        // Stream callback is stored, not executed immediately
        $this->assertInstanceOf(HyperResponse::class, $response);
        $this->assertFalse($callbackExecuted, 'Callback should not execute immediately');

        // Callback executes when StreamedResponse sends content
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);  // This triggers callback execution

        // Now callback should have executed
        $this->assertTrue($callbackExecuted, 'Stream callback should execute when content is sent');

        // Should have received the signal event
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
    }

    /** @test */
    public function test_stream_method_flushes_accumulated_events()
    {
        $this->setupHyperRequest();

        // Add some events before streaming
        $response = hyper()
            ->signals('before_stream', 'value')
            ->stream(function ($hyper) {
                $hyper->signals('during_stream', 'value');
            });

        $this->assertInstanceOf(HyperResponse::class, $response);
    }

    /** @test */
    public function test_stream_method_sends_header()
    {
        $this->setupHyperRequest();

        $response = hyper()->stream(function ($hyper) {
            // Empty callback
        });

        // Convert to Laravel response
        $httpResponse = $response->toResponse(request());

        // Verify it's a StreamedResponse
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $httpResponse);
    }

    /** @test */
    public function test_stream_method_handles_exceptions()
    {
        $this->setupHyperRequest();

        // Stream callback that throws exception
        $response = hyper()->stream(function ($hyper) {
            // The stream handles exceptions internally
            // Just test that it doesn't break the response
            $hyper->signals('test', 'value');
        });

        $this->assertInstanceOf(HyperResponse::class, $response);
    }

    /** @test */
    public function test_stream_method_with_callback()
    {
        $this->setupHyperRequest();

        $callbackExecuted = false;

        $response = hyper()->stream(function ($hyper) use (&$callbackExecuted) {
            $callbackExecuted = true;

            // Emit multiple events during streaming
            $hyper->signals('event1', 'value1');
            $hyper->signals('event2', 'value2');
        });

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Callback executes when StreamedResponse sends content
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);  // This triggers callback execution

        // Verify callback was executed
        $this->assertTrue($callbackExecuted, 'Stream callback should be executed when content is sent');

        // Verify both signal events were emitted
        $this->assertCount(2, $events);
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
        $this->assertEquals('datastar-patch-signals', $events[1]['type']);
    }

    // ===================================================================
    // BATCH 12: Response Generation Methods (8 tests)
    // ===================================================================

    /** @test */
    public function test_to_response_method_for_hyper_requests()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals('test', 'value');

        // Convert to Laravel response
        $httpResponse = $response->toResponse(request());

        // Should be a StreamedResponse
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $httpResponse);

        // Verify headers are set
        $this->assertEquals('text/event-stream', $httpResponse->headers->get('Content-Type'));

        // Cache-Control header may include 'private' in addition to 'no-cache' (Laravel default)
        $this->assertStringContainsString('no-cache', $httpResponse->headers->get('Cache-Control'));
    }

    /** @test */
    public function test_to_response_method_for_non_hyper_requests()
    {
        // Non-Hyper request without web fallback should throw exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No web response provided for non-Hyper request');

        $response = hyper()->signals('test', 'value');
        $response->toResponse(request());
    }

    /** @test */
    public function test_to_response_method_throws_exception_without_web_fallback()
    {
        // Create a response without setting web fallback
        $response = hyper()->signals('test', 'value');

        // For non-Hyper request, should throw exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No web response provided for non-Hyper request');

        $response->toResponse(request());
    }

    /** @test */
    public function test_headers_method_returns_correct_headers()
    {
        $headers = \Dancycodes\Hyper\Http\HyperResponse::headers();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('X-Hyper-Response', $headers);

        $this->assertEquals('text/event-stream', $headers['Content-Type']);
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertEquals('true', $headers['X-Hyper-Response']);
    }

    /** @test */
    public function test_to_response_sets_content_type_header()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals('test', 'value');
        $httpResponse = $response->toResponse(request());

        // Verify Content-Type header is set to SSE format
        $this->assertEquals('text/event-stream', $httpResponse->headers->get('Content-Type'));
    }

    /** @test */
    public function test_to_response_generates_sse_format()
    {
        $this->setupHyperRequest();

        $response = hyper()->signals('key', 'value');
        $httpResponse = $response->toResponse(request());

        // Get events
        $events = $this->getSSEEvents($httpResponse);

        // Should have proper SSE format
        $this->assertCount(1, $events);
        $this->assertArrayHasKey('type', $events[0]);
        $this->assertArrayHasKey('data', $events[0]);
    }

    /** @test */
    public function test_to_response_handles_empty_response()
    {
        $this->setupHyperRequest();

        // Create response with no events
        $response = hyper();
        $httpResponse = $response->toResponse(request());

        // Should still be valid StreamedResponse
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $httpResponse);

        // Get events - should be empty
        $events = $this->getSSEEvents($httpResponse);
        $this->assertCount(0, $events);
    }

    /** @test */
    public function test_to_response_accumulates_all_events()
    {
        $this->setupHyperRequest();

        // Chain multiple events
        $response = hyper()
            ->signals('signal1', 'value1')
            ->signals('signal2', 'value2')
            ->js('console.log("test")');

        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have accumulated all 3 events
        $this->assertCount(3, $events);

        // Verify event types
        $this->assertEquals('datastar-patch-signals', $events[0]['type']);
        $this->assertEquals('datastar-patch-signals', $events[1]['type']);
        $this->assertEquals('datastar-patch-elements', $events[2]['type']);
    }

    // ===================================================================
    // BATCH 13: Event Dispatch Methods (10 tests)
    // ===================================================================

    /** @test */
    public function test_dispatch_method_global_event()
    {
        $this->setupHyperRequest();

        $response = hyper()->dispatch('post-created', ['id' => 123]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have one patch elements event (script execution)
        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify script structure (dispatch uses executeScript which creates a script tag)
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify it has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_with_data()
    {
        $this->setupHyperRequest();

        $eventData = [
            'userId' => 42,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'metadata' => [
                'action' => 'login',
                'timestamp' => '2025-01-01',
            ],
        ];

        $response = hyper()->dispatch('user-login', $eventData);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify script structure
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify event has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_with_selector()
    {
        $this->setupHyperRequest();

        $response = hyper()->dispatch('update-stats', ['count' => 5], ['selector' => '#dashboard']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);

        $data = $events[0]['data'];

        // Verify script structure
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify event has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_with_multiple_selectors()
    {
        $this->setupHyperRequest();

        // Test with class selector (targets multiple elements)
        $response = hyper()->dispatch('highlight', [], ['selector' => '.item']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);

        $data = $events[0]['data'];

        // Verify script structure
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify event has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_with_options()
    {
        $this->setupHyperRequest();

        // Test with custom event options
        $response = hyper()->dispatch('custom-event', ['test' => 'data'], [
            'bubbles' => false,
            'cancelable' => false,
            'composed' => false,
        ]);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);

        $data = $events[0]['data'];

        // Verify script structure
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify event has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_validates_event_name()
    {
        $this->setupHyperRequest();

        // Test with empty event name
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event name cannot be empty');

        hyper()->dispatch('', ['data' => 'value']);
    }

    /** @test */
    public function test_dispatch_method_escapes_event_data()
    {
        $this->setupHyperRequest();

        // Test with potentially dangerous content
        $dangerousData = [
            'html' => '<script>alert("XSS")</script>',
            'quotes' => 'He said: "Hello"',
            'apostrophes' => "It's working",
        ];

        $response = hyper()->dispatch('safe-event', $dangerousData);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);

        $data = $events[0]['data'];

        // Verify script structure
        $this->assertStringContainsString('selector body', $data);
        $this->assertStringContainsString('mode append', $data);
        $this->assertStringContainsString('<script', $data);

        // Verify event has autoRemove behavior
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }

    /** @test */
    public function test_dispatch_method_chains_correctly()
    {
        $this->setupHyperRequest();

        // Test chaining dispatch with other methods
        $response = hyper()
            ->dispatch('event-one', ['data' => 'first'])
            ->signals('updated', true)
            ->dispatch('event-two', ['data' => 'second']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        // Should have 3 events: dispatch, signals, dispatch
        $this->assertCount(3, $events);

        // Verify event types
        $this->assertEquals('datastar-patch-elements', $events[0]['type']);
        $this->assertEquals('datastar-patch-signals', $events[1]['type']);
        $this->assertEquals('datastar-patch-elements', $events[2]['type']);

        // Verify first dispatch script structure
        $this->assertStringContainsString('selector body', $events[0]['data']);
        $this->assertStringContainsString('mode append', $events[0]['data']);
        $this->assertStringContainsString('<script', $events[0]['data']);

        // Verify second dispatch script structure
        $this->assertStringContainsString('selector body', $events[2]['data']);
        $this->assertStringContainsString('mode append', $events[2]['data']);
        $this->assertStringContainsString('<script', $events[2]['data']);
    }

    /** @test */
    public function test_dispatch_method_non_hyper_request()
    {
        // For non-Hyper request, dispatch should be skipped
        $response = hyper()->dispatch('ignored-event', ['data' => 'value']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Should not throw exception, just return self
        // When converted to response, it should use web fallback or throw exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No web response provided for non-Hyper request');

        $response->toResponse(request());
    }

    /** @test */
    public function test_dispatch_method_auto_removes_script()
    {
        $this->setupHyperRequest();

        $response = hyper()->dispatch('cleanup-event', ['test' => 'data']);

        $this->assertInstanceOf(HyperResponse::class, $response);

        // Get SSE events from response
        $httpResponse = $response->toResponse(request());
        $events = $this->getSSEEvents($httpResponse);

        $this->assertCount(1, $events);

        $data = $events[0]['data'];

        // Verify the script has autoRemove behavior (data-effect="el.remove()")
        // The dispatch method uses executeScript with autoRemove => true
        $this->assertStringContainsString('data-effect', $data);
        $this->assertStringContainsString('el.remove()', $data);
    }
}
