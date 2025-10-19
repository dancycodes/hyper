<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Http\HyperRedirect;
use Dancycodes\Hyper\Http\HyperResponse;
use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\HyperServiceProvider;
use Dancycodes\Hyper\Services\HyperFileStorage;
use Dancycodes\Hyper\Services\HyperSignalsDirective;
use Dancycodes\Hyper\Services\HyperUrlManager;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

/**
 * Test the HyperServiceProvider class
 *
 * @see TESTING.md - File 45: HyperServiceProvider Tests
 * Status: 🔄 IN PROGRESS - 20 test methods
 */
class HyperServiceProviderTest extends TestCase
{
    protected static $latestResponse;

    /** @test */
    public function test_service_provider_registers_hyper_response()
    {
        // The hyper.response singleton should be registered
        $this->assertTrue($this->app->bound('hyper.response'));
        // It should return a HyperResponse instance
        $instance1 = $this->app->make('hyper.response');
        $this->assertInstanceOf(HyperResponse::class, $instance1);
        // It should be a singleton (same instance)
        $instance2 = $this->app->make('hyper.response');
        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function test_service_provider_registers_hyper_signal()
    {
        // HyperSignal should be bound as singleton
        $this->assertTrue($this->app->bound(HyperSignal::class));
        // It should return a HyperSignal instance
        $instance = $this->app->make(HyperSignal::class);
        $this->assertInstanceOf(HyperSignal::class, $instance);
        // Check alias
        $this->assertTrue($this->app->bound('hyper.signals'));
        $aliasInstance = $this->app->make('hyper.signals');
        $this->assertInstanceOf(HyperSignal::class, $aliasInstance);
    }

    /** @test */
    public function test_service_provider_registers_hyper_storage()
    {
        // HyperFileStorage should be bound as singleton
        $this->assertTrue($this->app->bound(HyperFileStorage::class));
        // It should return a HyperFileStorage instance
        $instance = $this->app->make(HyperFileStorage::class);
        $this->assertInstanceOf(HyperFileStorage::class, $instance);
        // Check alias
        $this->assertTrue($this->app->bound('hyper.storage'));
        $aliasInstance = $this->app->make('hyper.storage');
        $this->assertInstanceOf(HyperFileStorage::class, $aliasInstance);
    }

    /** @test */
    public function test_service_provider_registers_url_manager()
    {
        // HyperUrlManager should be bound as singleton
        $this->assertTrue($this->app->bound(HyperUrlManager::class));
        // It should return a HyperUrlManager instance
        $instance = $this->app->make(HyperUrlManager::class);
        $this->assertInstanceOf(HyperUrlManager::class, $instance);
    }

    /** @test */
    public function test_service_provider_registers_signals_directive()
    {
        // HyperSignalsDirective service should be bound
        $this->assertTrue($this->app->bound('hyper.signals.directive'));
        // It should return a HyperSignalsDirective instance
        $instance = $this->app->make('hyper.signals.directive');
        $this->assertInstanceOf(HyperSignalsDirective::class, $instance);
    }

    /** @test */
    public function test_service_provider_merges_config()
    {
        // The hyper config should be available
        $this->assertNotNull(config('hyper'));
        // Check some default config values
        $this->assertIsArray(config('hyper.route_discovery'));
        $this->assertIsBool(config('hyper.route_discovery.enabled'));
    }

    /** @test */
    public function test_service_provider_publishes_assets()
    {
        // Clear any existing published assets
        $assetsPath = public_path('vendor/hyper/js');
        if (File::exists($assetsPath)) {
            File::deleteDirectory(dirname($assetsPath));
        }
        // Run the publish command
        Artisan::call('vendor:publish', [
            '--tag' => 'hyper-assets',
            '--force' => true,
        ]);
        // Check that assets were published
        $this->assertTrue(File::exists($assetsPath));
        // Cleanup
        if (File::exists($assetsPath)) {
            File::deleteDirectory(dirname($assetsPath));
        }
    }

    /** @test */
    public function test_service_provider_publishes_config()
    {
        // Clear any existing published config
        $configPath = config_path('hyper.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
        }
        // Run the publish command
        Artisan::call('vendor:publish', [
            '--tag' => 'hyper-config',
            '--force' => true,
        ]);
        // Check that config was published
        $this->assertTrue(File::exists($configPath));
        // Cleanup
        if (File::exists($configPath)) {
            File::delete($configPath);
        }
    }

    /** @test */
    public function test_service_provider_loads_helpers()
    {
        // Helper functions should be available
        $this->assertTrue(function_exists('hyper'));
        $this->assertTrue(function_exists('signals'));
        $this->assertTrue(function_exists('hyperStorage'));
    }

    /** @test */
    public function test_service_provider_registers_validation_rules()
    {
        // All base64 validation rules should be available
        // Use invalid base64 data that will fail validation
        $invalidBase64 = 'invalid!!!base64';
        // Test b64image - should fail for invalid base64
        $testValidator = Validator::make(
            ['file' => $invalidBase64],
            ['file' => 'b64image']
        );
        $this->assertFalse($testValidator->passes());
        // Test b64file - should fail for invalid base64
        $testValidator = Validator::make(
            ['file' => $invalidBase64],
            ['file' => 'b64file']
        );
        $this->assertFalse($testValidator->passes());
        // Test b64max - should fail for invalid base64
        $testValidator = Validator::make(
            ['file' => $invalidBase64],
            ['file' => 'b64max:10']
        );
        $this->assertFalse($testValidator->passes());
        // Test b64min - should fail for invalid base64
        $testValidator = Validator::make(
            ['file' => $invalidBase64],
            ['file' => 'b64min:10']
        );
        $this->assertFalse($testValidator->passes());
        // Test b64mimes - should fail for invalid base64
        $testValidator = Validator::make(
            ['file' => $invalidBase64],
            ['file' => 'b64mimes:png,jpg']
        );
        $this->assertFalse($testValidator->passes());
    }

    /** @test */
    public function test_service_provider_registers_blade_directives()
    {
        // @hyper directive
        $hyperDirective = Blade::compileString('@hyper');
        $this->assertStringContainsString('csrf-token', $hyperDirective);
        $this->assertStringContainsString('vendor/hyper/js/hyper.js', $hyperDirective);
        // @ifhyper directive
        $ifhyperDirective = Blade::compileString('@ifhyper test @endifhyper');
        $this->assertStringContainsString('if', $ifhyperDirective);
    }

    /** @test */
    public function test_service_provider_registers_request_macros()
    {
        $request = Request::create('/', 'GET');
        // isHyper macro should exist and work
        $this->assertTrue(Request::hasMacro('isHyper'));
        $this->assertFalse($request->isHyper());
        // signals macro should exist and work
        $this->assertTrue(Request::hasMacro('signals'));
        $signals = $request->signals();
        $this->assertInstanceOf(HyperSignal::class, $signals);
        // isHyperNavigate macro should exist and work
        $this->assertTrue(Request::hasMacro('isHyperNavigate'));
        $this->assertFalse($request->isHyperNavigate());
        // hyperNavigateKey macro should exist and work
        $this->assertTrue(Request::hasMacro('hyperNavigateKey'));
        $this->assertNull($request->hyperNavigateKey());
        // hyperNavigateKeys macro should exist and work
        $this->assertTrue(Request::hasMacro('hyperNavigateKeys'));
        $this->assertIsArray($request->hyperNavigateKeys());
    }

    /** @test */
    public function test_service_provider_registers_response_macros()
    {
        // hyper macro should be available on ResponseFactory
        $response = response()->hyper();
        $this->assertInstanceOf(HyperResponse::class, $response);
    }

    /** @test */
    public function test_service_provider_registers_view_macros()
    {
        // renderFragment macro should exist
        $this->assertTrue(View::hasMacro('renderFragment'));
    }

    /** @test */
    public function test_service_provider_boots_route_discovery_when_enabled()
    {
        // Enable route discovery
        Config::set('hyper.route_discovery.enabled', true);
        Config::set('hyper.route_discovery.discover_controllers_in_directory', []);
        Config::set('hyper.route_discovery.discover_views_in_directory', []);
        // Re-register the service provider
        $provider = new HyperServiceProvider($this->app);
        $provider->boot();
        // This test just verifies the boot process doesn't throw errors
        $this->assertTrue(true);
    }

    /** @test */
    public function test_service_provider_skips_route_discovery_when_disabled()
    {
        // Disable route discovery
        Config::set('hyper.route_discovery.enabled', false);
        // Re-register the service provider
        $provider = new HyperServiceProvider($this->app);
        $provider->boot();
        // This test just verifies the boot process doesn't throw errors
        $this->assertTrue(true);
    }

    /** @test */
    public function test_service_provider_provides_correct_services()
    {
        $provider = new HyperServiceProvider($this->app);
        $provides = $provider->provides();
        // Should list the services it provides
        $this->assertIsArray($provides);
        $this->assertContains(HyperSignal::class, $provides);
        $this->assertContains('hyper.signals', $provides);
    }

    /** @test */
    public function test_service_provider_registers_hyper_redirect()
    {
        // HyperRedirect should be bound
        $this->assertTrue($this->app->bound(HyperRedirect::class));
        // It should be creatable when providing required parameters
        $hyperResponse = new HyperResponse;
        $instance = new HyperRedirect('/test-url', $hyperResponse);
        $this->assertInstanceOf(HyperRedirect::class, $instance);
    }

    /** @test */
    public function test_service_provider_registers_fragment_directives()
    {
        // @fragment directive
        $fragmentDirective = Blade::compileString('@fragment("test") content @endfragment');
        // The directives should compile without errors
        $this->assertIsString($fragmentDirective);
    }

    /** @test */
    public function test_helpers_return_correct_instances()
    {
        // hyper() helper should return HyperResponse singleton
        $hyper1 = hyper();
        $hyper2 = hyper();
        $this->assertInstanceOf(HyperResponse::class, $hyper1);
        $this->assertSame($hyper1, $hyper2);
        // signals() helper should return HyperSignal instance
        $signals = signals();
        $this->assertInstanceOf(HyperSignal::class, $signals);
        // hyperStorage() helper should return HyperFileStorage instance
        $storage = hyperStorage();
        $this->assertInstanceOf(HyperFileStorage::class, $storage);
    }
}
