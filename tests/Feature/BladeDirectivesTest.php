<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * Test Blade Directives Integration
 *
 * @see TESTING.md - File 46: BladeDirectives Tests
 * Status: ✅ COMPLETE - 20 test methods (includes 5 new tests for simplified @signals)
 */
class BladeDirectivesTest extends TestCase
{
    protected static $latestResponse;

    protected string $viewsPath;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary views directory for testing
        $this->viewsPath = storage_path('framework/testing/views');
        if (!File::exists($this->viewsPath)) {
            File::makeDirectory($this->viewsPath, 0755, true);
        }
        // Add our test views path to Laravel's view finder
        View::addLocation($this->viewsPath);
    }

    protected function tearDown(): void
    {
        // Clean up test views
        if (File::exists($this->viewsPath)) {
            File::deleteDirectory($this->viewsPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function test_hyper_directive_renders_script_tag()
    {
        $compiled = Blade::compileString('@hyper');
        // Should include a script tag
        $this->assertStringContainsString('<script', $compiled);
        $this->assertStringContainsString('</script>', $compiled);
    }

    /** @test */
    public function test_hyper_directive_includes_csrf_token()
    {
        $compiled = Blade::compileString('@hyper');
        // Should include CSRF meta tag
        $this->assertStringContainsString('csrf-token', $compiled);
        $this->assertStringContainsString('meta', $compiled);
    }

    /** @test */
    public function test_hyper_directive_uses_correct_asset_path()
    {
        $compiled = Blade::compileString('@hyper');
        // Should reference the correct asset path
        $this->assertStringContainsString('vendor/hyper/js/hyper.js', $compiled);
        $this->assertStringContainsString('type="module"', $compiled);
    }

    /** @test */
    public function test_signals_directive_renders_with_array()
    {
        // Create a test view with @signals directive
        $viewContent = <<<'BLADE'
<div @signals(['count' => 0, 'name' => 'Test'])>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-array.blade.php', $viewContent);
        $rendered = View::make('signals-array')->render();
        // Should render data-signals attribute
        $this->assertStringContainsString('data-signals', $rendered);
    }

    /** @test */
    public function test_signals_directive_renders_with_variables()
    {
        // Create a test view
        $viewContent = <<<'BLADE'
@php
    $count = 5;
    $name = 'John';
@endphp
<div @signals($count, $name)>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-vars.blade.php', $viewContent);
        $rendered = View::make('signals-vars')->render();
        // Should render data-signals attribute
        $this->assertStringContainsString('data-signals', $rendered);
    }

    /** @test */
    public function test_signals_directive_renders_with_spread()
    {
        // Create a test view with spread operator
        $viewContent = <<<'BLADE'
@php
    $user = ['name' => 'John', 'email' => 'john@example.com'];
@endphp
<div @signals(...$user)>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-spread.blade.php', $viewContent);
        $rendered = View::make('signals-spread')->render();
        // Should render data-signals attribute with spread data
        $this->assertStringContainsString('data-signals', $rendered);
    }

    /** @test */
    public function test_signals_directive_escapes_html()
    {
        // Create a test view with HTML in data
        $viewContent = <<<'BLADE'
<div @signals(['message' => '<script>alert("xss")</script>'])>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-escape.blade.php', $viewContent);
        $rendered = View::make('signals-escape')->render();
        // Should escape HTML entities
        $this->assertStringContainsString('data-signals', $rendered);
        // The HTML should be JSON-encoded, which handles escaping
        $this->assertStringNotContainsString('<script>alert', $rendered);
    }

    /** @test */
    public function test_signals_directive_with_empty_data()
    {
        // Create a test view with empty signals
        $viewContent = <<<'BLADE'
<div @signals([])>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-empty.blade.php', $viewContent);
        $rendered = View::make('signals-empty')->render();
        // Should still render data-signals attribute
        $this->assertStringContainsString('data-signals', $rendered);
    }

    /** @test */
    public function test_ifhyper_directive_for_hyper_requests()
    {
        // Create a test view with @ifhyper
        $viewContent = <<<'BLADE'
@ifhyper
    <div>Hyper content</div>
@endifhyper
BLADE;
        File::put($this->viewsPath . '/ifhyper-test.blade.php', $viewContent);
        // Make a Hyper request
        $response = $this->call('GET', '/', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        // Render the view with Hyper request context
        request()->headers->set('Datastar-Request', 'true');
        $rendered = View::make('ifhyper-test')->render();
        // Should show Hyper content
        $this->assertStringContainsString('Hyper content', $rendered);
    }

    /** @test */
    public function test_ifhyper_directive_for_normal_requests()
    {
        // Create a test view with @ifhyper
        $viewContent = <<<'BLADE'
@ifhyper
    <div>Hyper content</div>
@endifhyper
BLADE;
        File::put($this->viewsPath . '/ifhyper-normal.blade.php', $viewContent);
        // Make a normal request (clear any Hyper headers)
        request()->headers->remove('Datastar-Request');
        $rendered = View::make('ifhyper-normal')->render();
        // Should NOT show Hyper content
        $this->assertStringNotContainsString('Hyper content', $rendered);
    }

    /** @test */
    public function test_ifhyper_with_else_block()
    {
        // Create a test view with @ifhyper and @else
        $viewContent = <<<'BLADE'
@ifhyper
    <div>Hyper content</div>
@else
    <div>Regular content</div>
@endifhyper
BLADE;
        File::put($this->viewsPath . '/ifhyper-else.blade.php', $viewContent);
        // Test with normal request
        request()->headers->remove('Datastar-Request');
        $rendered = View::make('ifhyper-else')->render();
        // Should show regular content
        $this->assertStringContainsString('Regular content', $rendered);
        $this->assertStringNotContainsString('Hyper content', $rendered);
    }

    /** @test */
    public function test_fragment_directive_in_view()
    {
        // Create a test view with @fragment
        $viewContent = <<<'BLADE'
@fragment('test-fragment')
    <div>Fragment content</div>
@endfragment
BLADE;
        File::put($this->viewsPath . '/fragment-test.blade.php', $viewContent);
        // Should compile without errors
        $rendered = View::make('fragment-test')->render();
        // Fragment content should still be visible in full view
        $this->assertStringContainsString('Fragment content', $rendered);
    }

    /** @test */
    public function test_endfragment_directive_in_view()
    {
        // Create a test view with multiple fragments
        $viewContent = <<<'BLADE'
@fragment('fragment-one')
    <div>First fragment</div>
@endfragment
@fragment('fragment-two')
    <div>Second fragment</div>
@endfragment
BLADE;
        File::put($this->viewsPath . '/multi-fragment.blade.php', $viewContent);
        // Should compile without errors
        $rendered = View::make('multi-fragment')->render();
        // Both fragments should be visible
        $this->assertStringContainsString('First fragment', $rendered);
        $this->assertStringContainsString('Second fragment', $rendered);
    }

    /** @test */
    public function test_nested_directives()
    {
        // Create a test view with nested directives
        $viewContent = <<<'BLADE'
@ifhyper
    @fragment('hyper-fragment')
        <div @signals(['nested' => true])>
            Nested content
        </div>
    @endfragment
@endifhyper
BLADE;
        File::put($this->viewsPath . '/nested-directives.blade.php', $viewContent);
        // Test with Hyper request
        request()->headers->set('Datastar-Request', 'true');
        $rendered = View::make('nested-directives')->render();
        // Should compile and render correctly
        $this->assertStringContainsString('Nested content', $rendered);
    }

    /** @test */
    public function test_signals_directive_with_local_signal()
    {
        // Test that local signals (prefix underscore) use literal variable name
        $viewContent = <<<'BLADE'
@php
    $_editing = true;  // Variable name includes underscore
    $visible = false;
@endphp
<div @signals($_editing, $visible)>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-local.blade.php', $viewContent);
        $rendered = View::make('signals-local')->render();
        // Should render data-signals with _editing and visible
        $this->assertStringContainsString('data-signals', $rendered);
        $this->assertStringContainsString('_editing', $rendered);
        $this->assertStringContainsString('visible', $rendered);
    }

    /** @test */
    public function test_signals_directive_with_locked_signal()
    {
        // Test that locked signals (suffix underscore) use literal variable name
        $viewContent = <<<'BLADE'
@php
    $userId_ = 123;  // Variable name includes trailing underscore
    $name = 'John';
@endphp
<div @signals($userId_, $name)>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-locked.blade.php', $viewContent);
        $rendered = View::make('signals-locked')->render();
        // Should render data-signals with userId_ and name
        $this->assertStringContainsString('data-signals', $rendered);
        $this->assertStringContainsString('userId_', $rendered);
        $this->assertStringContainsString('name', $rendered);
    }

    /** @test */
    public function test_signals_directive_with_spread_and_underscores()
    {
        // Test that spread with underscored variables works correctly
        $viewContent = <<<'BLADE'
@php
    $_uiState = ['modal' => false, 'sidebar' => true];
    $permissions_ = ['canEdit' => true, 'canDelete' => false];
@endphp
<div @signals(...$_uiState, ...$permissions_)>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-spread-underscore.blade.php', $viewContent);
        $rendered = View::make('signals-spread-underscore')->render();
        // Should render data-signals with spread values
        $this->assertStringContainsString('data-signals', $rendered);
        $this->assertStringContainsString('modal', $rendered);
        $this->assertStringContainsString('canEdit', $rendered);
    }

    /** @test */
    public function test_signals_directive_with_compact()
    {
        // Test that compact() function works correctly
        $viewContent = <<<'BLADE'
@php
    $username = 'john_doe';
    $email = 'john@example.com';
    $age = 25;
@endphp
<div @signals(compact('username', 'email', 'age'))>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-compact.blade.php', $viewContent);
        $rendered = View::make('signals-compact')->render();
        // Should render data-signals with compacted variables
        $this->assertStringContainsString('data-signals', $rendered);
        $this->assertStringContainsString('username', $rendered);
        $this->assertStringContainsString('john_doe', $rendered);
        $this->assertStringContainsString('email', $rendered);
        $this->assertStringContainsString('john@example.com', $rendered);
    }

    /** @test */
    public function test_signals_directive_mixed_patterns()
    {
        // Test mixing regular, local, locked signals with compact and spread
        $viewContent = <<<'BLADE'
@php
    $name = 'John';
    $_loading = false;
    $role_ = 'admin';
    $meta = ['version' => '1.0'];
@endphp
<div @signals($name, $_loading, $role_, ...$meta, compact('name'))>
    Content
</div>
BLADE;
        File::put($this->viewsPath . '/signals-mixed.blade.php', $viewContent);
        $rendered = View::make('signals-mixed')->render();
        // Should render all patterns correctly
        $this->assertStringContainsString('data-signals', $rendered);
        $this->assertStringContainsString('name', $rendered);
        $this->assertStringContainsString('_loading', $rendered);
        $this->assertStringContainsString('role_', $rendered);
        $this->assertStringContainsString('version', $rendered);
    }

    /** @test */
    public function test_directive_compilation_does_not_error()
    {
        // Test that all directives compile without throwing exceptions
        $directives = [
            '@hyper',
            '@signals(["test" => "value"])',
            '@ifhyper content @endifhyper',
            '@fragment("test") content @endfragment',
        ];
        foreach ($directives as $directive) {
            try {
                $compiled = Blade::compileString($directive);
                $this->assertIsString($compiled);
            } catch (\Exception $e) {
                $this->fail("Directive '{$directive}' failed to compile: " . $e->getMessage());
            }
        }
    }

    // ===================================================================
    // @dispatch Directive Tests (8 tests)
    // ===================================================================
    /** @test */
    public function test_dispatch_directive_renders_global_event()
    {
        // Create a test view with @dispatch directive
        $viewContent = <<<'BLADE'
@dispatch('post-created', ['id' => 123])
BLADE;
        File::put($this->viewsPath . '/dispatch-global.blade.php', $viewContent);
        $rendered = View::make('dispatch-global')->render();
        // Should render a script tag with window.dispatchEvent
        $this->assertStringContainsString('<script>', $rendered);
        $this->assertStringContainsString('</script>', $rendered);
        $this->assertStringContainsString('window.dispatchEvent', $rendered);
        $this->assertStringContainsString('CustomEvent', $rendered);
        $this->assertStringContainsString('post-created', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_with_event_data()
    {
        // Create a test view with event data
        $viewContent = <<<'BLADE'
@dispatch('user-login', ['userId' => 42, 'email' => 'john@example.com'])
BLADE;
        File::put($this->viewsPath . '/dispatch-data.blade.php', $viewContent);
        $rendered = View::make('dispatch-data')->render();
        // Should render the event data in the script
        $this->assertStringContainsString('user-login', $rendered);
        $this->assertStringContainsString('userId', $rendered);
        $this->assertStringContainsString('42', $rendered);
        $this->assertStringContainsString('john@example.com', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_with_selector()
    {
        // Create a test view with targeted dispatch
        $viewContent = <<<'BLADE'
@dispatch('update-stats', ['count' => 5], ['selector' => '#dashboard'])
BLADE;
        File::put($this->viewsPath . '/dispatch-selector.blade.php', $viewContent);
        $rendered = View::make('dispatch-selector')->render();
        // Should render with querySelectorAll and selector
        $this->assertStringContainsString('querySelectorAll', $rendered);
        $this->assertStringContainsString('#dashboard', $rendered);
        $this->assertStringContainsString('update-stats', $rendered);
        // Should NOT dispatch to window
        $this->assertStringNotContainsString('window.dispatchEvent', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_with_body_target()
    {
        // Create a test view that dispatches to body
        $viewContent = <<<'BLADE'
@dispatch('body-event', ['data' => 'value'], ['window' => false])
BLADE;
        File::put($this->viewsPath . '/dispatch-body.blade.php', $viewContent);
        $rendered = View::make('dispatch-body')->render();
        // Should render with document.body.dispatchEvent
        $this->assertStringContainsString('document.body.dispatchEvent', $rendered);
        $this->assertStringContainsString('body-event', $rendered);
        // Should NOT dispatch to window
        $this->assertStringNotContainsString('window.dispatchEvent', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_escapes_html()
    {
        // Create a test view with potentially dangerous content
        $viewContent = <<<'BLADE'
@dispatch('safe-event', ['html' => '<script>alert("XSS")</script>'])
BLADE;
        File::put($this->viewsPath . '/dispatch-escape.blade.php', $viewContent);
        $rendered = View::make('dispatch-escape')->render();
        // Should properly escape HTML in the event data
        $this->assertStringContainsString('safe-event', $rendered);
        // The dangerous script should be JSON-encoded with hex escaping
        // JSON_HEX_TAG converts < and > to \u003C and \u003E (uppercase hex)
        $this->assertStringContainsString('\u003Cscript\u003E', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_with_empty_event_name_throws_exception()
    {
        // Create a test view with empty event name
        $viewContent = <<<'BLADE'
@dispatch('', ['data' => 'value'])
BLADE;
        File::put($this->viewsPath . '/dispatch-empty.blade.php', $viewContent);
        // Should throw an exception for empty event name
        // Laravel wraps Blade exceptions in ViewException
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('@dispatch directive requires an event name');
        View::make('dispatch-empty')->render();
    }

    /** @test */
    public function test_dispatch_directive_with_complex_data()
    {
        // Create a test view with nested data structures
        $viewContent = <<<'BLADE'
@php
    $complexData = [
        'user' => [
            'name' => 'John Doe',
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true
            ]
        ],
        'metadata' => [
            'timestamp' => '2025-01-01',
            'version' => '1.0'
        ]
    ];
@endphp
@dispatch('complex-event', $complexData)
BLADE;
        File::put($this->viewsPath . '/dispatch-complex.blade.php', $viewContent);
        $rendered = View::make('dispatch-complex')->render();
        // Should render all nested data correctly
        $this->assertStringContainsString('complex-event', $rendered);
        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString('dark', $rendered);
        $this->assertStringContainsString('timestamp', $rendered);
    }

    /** @test */
    public function test_dispatch_directive_compiles_correctly()
    {
        // Test that @dispatch compiles without errors
        $directives = [
            '@dispatch("event-name")',
            '@dispatch("event", ["data" => "value"])',
            '@dispatch("event", [], ["selector" => "#target"])',
        ];
        foreach ($directives as $directive) {
            try {
                $compiled = Blade::compileString($directive);
                $this->assertIsString($compiled);
                $this->assertStringContainsString('dispatchEvent', $compiled);
            } catch (\Exception $e) {
                $this->fail("Directive '{$directive}' failed to compile: " . $e->getMessage());
            }
        }
    }
}
