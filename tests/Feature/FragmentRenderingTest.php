<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Test Fragment Rendering Workflows
 *
 * @see TESTING.md - File 50: FragmentRendering Tests
 * Status: 🔄 IN PROGRESS - 18 test methods
 */
class FragmentRenderingTest extends TestCase
{
    protected string $viewsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewsPath = storage_path('framework/testing/views');
        if (!File::exists($this->viewsPath)) {
            File::makeDirectory($this->viewsPath, 0755, true);
        }
        View::addLocation($this->viewsPath);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->viewsPath)) {
            File::deleteDirectory($this->viewsPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function test_fragment_renders_in_initial_view()
    {
        File::put($this->viewsPath . '/test-fragment.blade.php', '
            @fragment("content")
                <div>Fragment content</div>
            @endfragment
        ');

        $rendered = View::make('test-fragment')->render();
        $this->assertStringContainsString('Fragment content', $rendered);
    }

    /** @test */
    public function test_fragment_updates_via_controller()
    {
        File::put($this->viewsPath . '/updateable-fragment.blade.php', '
            @fragment("dynamic")
                <div>{{ $message }}</div>
            @endfragment
        ');

        Route::get('/update-fragment', function () {
            return hyper()->fragment('updateable-fragment', 'dynamic', ['message' => 'Updated!']);
        });

        $response = $this->call('GET', '/update-fragment', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_multiple_fragments_in_single_view()
    {
        File::put($this->viewsPath . '/multi-fragment.blade.php', '
            @fragment("header")
                <header>Header</header>
            @endfragment
            @fragment("body")
                <main>Body</main>
            @endfragment
            @fragment("footer")
                <footer>Footer</footer>
            @endfragment
        ');

        $rendered = View::make('multi-fragment')->render();
        $this->assertStringContainsString('Header', $rendered);
        $this->assertStringContainsString('Body', $rendered);
        $this->assertStringContainsString('Footer', $rendered);
    }

    /** @test */
    public function test_nested_fragments()
    {
        File::put($this->viewsPath . '/nested-fragments.blade.php', '
            @fragment("outer")
                <div>Outer
                    @fragment("inner")
                        <span>Inner</span>
                    @endfragment
                </div>
            @endfragment
        ');

        $rendered = View::make('nested-fragments')->render();
        $this->assertStringContainsString('Outer', $rendered);
        $this->assertStringContainsString('Inner', $rendered);
    }

    /** @test */
    public function test_fragment_with_data_binding()
    {
        File::put($this->viewsPath . '/data-fragment.blade.php', '
            @fragment("data-bound")
                <div>{{ $name }} - {{ $count }}</div>
            @endfragment
        ');

        $rendered = View::make('data-fragment', ['name' => 'Test', 'count' => 5])->render();
        $this->assertStringContainsString('Test', $rendered);
        $this->assertStringContainsString('5', $rendered);
    }

    /** @test */
    public function test_fragment_with_signals()
    {
        File::put($this->viewsPath . '/signal-fragment.blade.php', '
            <div @signals(["count" => 0])>
                @fragment("counter")
                    <span data-text="$count"></span>
                @endfragment
            </div>
        ');

        $rendered = View::make('signal-fragment')->render();
        $this->assertStringContainsString('data-signals', $rendered);
    }

    /** @test */
    public function test_fragment_targeting_custom_selector()
    {
        Route::get('/custom-selector', function () {
            return hyper()->fragment('simple-fragment', 'content', ['msg' => 'Hi'], [
                'selector' => '#custom-target',
            ]);
        });

        File::put($this->viewsPath . '/simple-fragment.blade.php', '
            @fragment("content"){{ $msg }}@endfragment
        ');

        $response = $this->call('GET', '/custom-selector', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_fragment_with_different_modes()
    {
        File::put($this->viewsPath . '/mode-fragment.blade.php', '
            @fragment("test")Content@endfragment
        ');

        foreach (['inner', 'outer', 'append', 'prepend'] as $mode) {
            Route::get("/mode-{$mode}", function () use ($mode) {
                return hyper()->fragment('mode-fragment', 'test', [], ['mode' => $mode]);
            });

            $response = $this->call('GET', "/mode-{$mode}", [], [], [], [
                'HTTP_DATASTAR_REQUEST' => 'true',
            ]);

            $response->assertOk();
        }
    }

    /** @test */
    public function test_fragment_composition()
    {
        File::put($this->viewsPath . '/composite.blade.php', '
            @fragment("part-a")Part A@endfragment
            @fragment("part-b")Part B@endfragment
        ');

        Route::get('/composite', function () {
            return hyper()
                ->fragment('composite', 'part-a')
                ->fragment('composite', 'part-b');
        });

        $response = $this->call('GET', '/composite', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_fragment_with_slots()
    {
        File::put($this->viewsPath . '/slot-fragment.blade.php', '
            @fragment("with-slot")
                <div>{{ $slot ?? "Default slot" }}</div>
            @endfragment
        ');

        $rendered = View::make('slot-fragment', ['slot' => 'Custom content'])->render();
        $this->assertStringContainsString('Custom content', $rendered);
    }

    /** @test */
    public function test_fragment_error_handling()
    {
        $this->expectException(\Exception::class);

        // Try to render non-existent fragment
        View::renderFragment('non-existent', 'missing-fragment');
    }

    /** @test */
    public function test_fragment_with_missing_data()
    {
        File::put($this->viewsPath . '/optional-data.blade.php', '
            @fragment("test"){{ $optional ?? "default" }}@endfragment
        ');

        $rendered = View::make('optional-data')->render();
        $this->assertStringContainsString('default', $rendered);
    }

    /** @test */
    public function test_fragment_with_complex_html()
    {
        File::put($this->viewsPath . '/complex.blade.php', '
            @fragment("complex")
                <div class="container">
                    <h1>Title</h1>
                    <p>Paragraph with <strong>bold</strong> and <em>italic</em></p>
                    <ul>
                        <li>Item 1</li>
                        <li>Item 2</li>
                    </ul>
                </div>
            @endfragment
        ');

        $rendered = View::make('complex')->render();
        $this->assertStringContainsString('<div class="container">', $rendered);
        $this->assertStringContainsString('<strong>bold</strong>', $rendered);
    }

    /** @test */
    public function test_fragment_reactivity()
    {
        File::put($this->viewsPath . '/reactive.blade.php', '
            <div @signals(["active" => true])>
                @fragment("status")
                    <span data-show="$active">Active</span>
                @endfragment
            </div>
        ');

        $rendered = View::make('reactive')->render();
        $this->assertStringContainsString('data-show', $rendered);
    }

    /** @test */
    public function test_fragment_lifecycle()
    {
        Route::get('/lifecycle-1', function () {
            return hyper()->fragment('lifecycle-view', 'step1', ['step' => 1]);
        });

        Route::get('/lifecycle-2', function () {
            return hyper()->fragment('lifecycle-view', 'step2', ['step' => 2]);
        });

        File::put($this->viewsPath . '/lifecycle-view.blade.php', '
            @fragment("step1")Step {{ $step }}@endfragment
            @fragment("step2")Step {{ $step }}@endfragment
        ');

        $response1 = $this->call('GET', '/lifecycle-1', [], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response2 = $this->call('GET', '/lifecycle-2', [], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);

        $response1->assertOk();
        $response2->assertOk();
    }

    /** @test */
    public function test_fragment_render_fragment_macro()
    {
        File::put($this->viewsPath . '/macro-test.blade.php', '
            @fragment("test-macro")Hello from macro@endfragment
        ');

        $result = View::renderFragment('macro-test', 'test-macro');
        $this->assertStringContainsString('Hello from macro', $result);
    }

    /** @test */
    public function test_fragment_with_blade_directives()
    {
        File::put($this->viewsPath . '/directive-fragment.blade.php', '
            @fragment("directives")
                @if(true)
                    <div>Conditional content</div>
                @endif
                @foreach([1,2,3] as $num)
                    <span>{{ $num }}</span>
                @endforeach
            @endfragment
        ');

        $rendered = View::make('directive-fragment')->render();
        $this->assertStringContainsString('Conditional content', $rendered);
        $this->assertStringContainsString('<span>1</span>', $rendered);
    }

    /** @test */
    public function test_fragment_rendering_performance()
    {
        File::put($this->viewsPath . '/perf-fragment.blade.php', '
            @fragment("perf")
                @foreach(range(1, 100) as $i)
                    <div>Item {{ $i }}</div>
                @endforeach
            @endfragment
        ');

        $startTime = microtime(true);
        $rendered = View::make('perf-fragment')->render();
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(1000, $executionTime, 'Fragment rendering took too long');
        $this->assertStringContainsString('Item 1', $rendered);
        $this->assertStringContainsString('Item 100', $rendered);
    }
}
