<?php

namespace Dancycodes\Hyper;

use Dancycodes\Hyper\Html\Services\IconManager;
use Dancycodes\Hyper\Http\HyperRedirect;
use Dancycodes\Hyper\Http\HyperSignal;
use Dancycodes\Hyper\Services\HyperFileStorage;
use Dancycodes\Hyper\Services\HyperUrlManager;
use Dancycodes\Hyper\View\Fragment\BladeFragment;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Hyper Service Provider
 *
 * Registers and bootstraps the Hyper package within the Laravel application container.
 * This provider handles service binding, Request/Response macro registration, Blade
 * directive compilation, validation rule registration, and automatic route discovery.
 *
 * The provider is automatically loaded by Laravel when the package is installed,
 * as specified in the composer.json extra.laravel.providers configuration. It follows
 * Laravel's service provider lifecycle, executing register() during early application
 * bootstrapping and boot() after all providers have been registered.
 *
 * Core responsibilities include:
 * - Binding Hyper services as singletons in the container
 * - Registering Request macros for Hyper request detection and signal access
 * - Registering Response macros for fluent response building
 * - Compiling custom Blade directives (@hyper, @signals, @fragment, @dispatch)
 * - Registering base64 file validation rules
 * - Enabling automatic controller and view route discovery
 *
 *
 * @see \Dancycodes\Hyper\Http\HyperResponse
 * @see \Dancycodes\Hyper\Http\HyperSignal
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 */
class HyperServiceProvider extends ServiceProvider
{
    /**
     * Register package services in the application container
     *
     * Binds all core Hyper services as singletons to ensure consistent instances
     * throughout the request lifecycle. This method executes during early application
     * bootstrapping before the boot() method and before the application is fully ready.
     *
     * Services registered:
     * - HyperSignal: Signal state management and validation
     * - HyperResponse: SSE response builder
     * - HyperUrlManager: Navigate URL manipulation
     * - HyperFileStorage: Base64 file storage operations
     * - HyperRedirect: Full-page redirect responses
     */
    public function register(): void
    {
        require_once __DIR__ . '/helpers.php';

        $this->app->singleton(HyperSignal::class, function ($app) {
            return new HyperSignal($app['request']);
        });

        $this->app->alias(HyperSignal::class, 'hyper.signals');

        $this->app->singleton('hyper.response', function ($app) {
            return new \Dancycodes\Hyper\Http\HyperResponse;
        });

        $this->app->singleton(HyperUrlManager::class, function ($app) {
            return new HyperUrlManager($app['request']);
        });

        $this->app->singleton(HyperFileStorage::class);
        $this->app->alias(HyperFileStorage::class, 'hyper.storage');

        $this->app->singleton(IconManager::class);
        $this->app->alias(IconManager::class, 'hyper.icons');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/hyper.php',
            'hyper'
        );

        $this->app->bind(HyperRedirect::class);
    }

    /**
     * Bootstrap package services after container registration
     *
     * Executes after all service providers have completed their register() methods,
     * allowing access to all bound services. Publishes package assets, registers
     * macros, compiles Blade directives, and initializes route discovery.
     *
     * This method handles:
     * - Publishing JavaScript assets to public directory
     * - Publishing configuration files for application customization
     * - Registering Blade directives for reactive templates
     * - Extending Request and Response with Hyper-specific macros
     * - Registering custom base64 file validation rules
     * - Enabling automatic route discovery from controllers and views
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/js' => public_path('vendor/hyper/js'),
        ], 'hyper-assets');

        $this->publishes([
            __DIR__ . '/../config/hyper.php' => config_path('hyper.php'),
        ], 'hyper-config');

        $this->registerBladeDirectives();
        $this->registerFragmentDirectives();
        $this->registerFragmentMacros();
        $this->registerRequestMacros();
        $this->registerResponseMacros();
        $this->registerBase64ValidationRules();
        $this->registerRouteDiscovery();
        $this->registerSignalsDirective();
        $this->registerRedirectConversionMiddleware();
        $this->registerIconProviders();
    }

    /**
     * Register core Blade directives for Hyper functionality
     *
     * Registers essential Blade directives that enable Hyper's reactive capabilities:
     * - @hyper: Includes the Hyper JavaScript library and CSRF meta tag
     * - @hyperSignals: Injects initial signal state into the page
     * - @ifhyper: Conditional directive for Hyper vs regular requests
     * - @dispatch: Dispatches custom browser events on initial page load
     *
     * These directives compile at view rendering time and are cached with
     * Laravel's compiled Blade templates for optimal performance.
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('hyper', function () {
            return "<?php echo '<meta name=\"csrf-token\" content=\"' . csrf_token() . '\">' . chr(10) . '<script type=\"module\" src=\"' . asset('vendor/hyper/js/hyper.js') . '\"></script>'; ?>";
        });

        Blade::directive('hyperSignals', function ($expression) {
            return "<?php echo '<script>window.hyperSignals = ' . json_encode($expression ?: []) . ';</script>'; ?>";
        });

        Blade::if('ifhyper', function () {
            return request()->hasHeader('Datastar-Request');
        });
    }

    /**
     * Specify services provided by this service provider
     *
     * Declares which services this provider offers to the container, enabling
     * Laravel's deferred service provider optimization. The application will only
     * load this provider when one of the specified services is actually requested,
     * improving bootstrap performance for requests that don't use Hyper features.
     *
     * @return array<int, string> Array of service class names and aliases provided
     */
    public function provides(): array
    {
        return [
            HyperSignal::class,
            'hyper.signals',
        ];
    }

    /**
     * Register Blade directives for fragment support
     *
     * Implements a dual-registration strategy to prevent race conditions during
     * Blade compiler initialization:
     *
     * 1. Immediate registration: Attempts to register directives if the Blade
     *    compiler has already been resolved in the container
     * 2. Deferred registration: Uses callAfterResolving as a fallback to ensure
     *    directives are registered when compiler becomes available
     *
     * This approach prevents "unexpected end of file" compilation errors that
     * can occur when fragment directives are used before they are registered,
     * particularly in fresh package installations or after cache clearing.
     *
     * The @fragment and @endfragment directives are intentionally no-op directives
     * that serve as markers for the fragment parser to extract view sections.
     */
    private function registerFragmentDirectives(): void
    {
        $registerDirectives = function (BladeCompiler $blade) {
            // Only register if not already registered (prevents double registration)
            if (!isset($blade->getCustomDirectives()['fragment'])) {
                $blade->directive('fragment', static fn () => '');
                $blade->directive('endfragment', static fn () => '');
            }
        };

        // Attempt immediate registration if Blade compiler is already resolved
        if ($this->app->resolved('blade.compiler')) {
            try {
                $registerDirectives($this->app->make('blade.compiler'));
            } catch (\Throwable $e) {
                // Silently fail - callAfterResolving will handle it
            }
        }

        // Also register via callAfterResolving as a safety net
        $this->callAfterResolving('blade.compiler', $registerDirectives);
    }

    /**
     * Register View facade macros for fragment rendering
     *
     * Extends Laravel's View facade with a renderFragment() macro that provides
     * convenient access to Hyper's fragment rendering system. This allows fragments
     * to be rendered from anywhere in the application using View::renderFragment().
     *
     * The macro delegates to BladeFragment::render() which extracts and compiles
     * only the specified fragment section from a Blade view, enabling efficient
     * partial updates without rendering the entire view.
     *
     *
     * @see \Dancycodes\Hyper\View\Fragment\BladeFragment::render()
     */
    private function registerFragmentMacros(): void
    {
        View::macro('renderFragment', function (string $view, string $fragment, array $data = []) {
            return BladeFragment::render($view, $fragment, $data);
        });
    }

    /**
     * Register Request facade macros for Hyper request detection and signal access
     *
     * Extends Laravel's Request object with Hyper-specific methods:
     *
     * - isHyper(): Detects if the current request is a Hyper reactive request
     *   by checking for the Datastar-Request header
     * - signals(): Provides access to the HyperSignal instance or retrieves
     *   specific signal values with optional defaults
     * - isHyperNavigate(): Checks if the request is a navigate request and
     *   optionally validates specific navigate keys
     * - hyperNavigateKey(): Retrieves the navigate key(s) from request headers
     * - hyperNavigateKeys(): Returns navigate keys as an array for multi-key scenarios
     *
     * These macros enable conditional logic and signal access throughout the
     * application using familiar Request facade syntax.
     *
     *
     * @see \Dancycodes\Hyper\Http\HyperSignal
     */
    private function registerRequestMacros(): void
    {
        // Check if the request is a Hyper request
        Request::macro('isHyper', function () {
            return $this->hasHeader('Datastar-Request');
        });

        // Signals access macro
        Request::macro('signals', function (?string $key = null, mixed $default = null) {
            $hyperSignal = app(HyperSignal::class);

            if (is_null($key)) {
                return $hyperSignal;
            }

            return $hyperSignal->get($key, $default);
        });

        // Check if request is a Hyper navigate request
        Request::macro('isHyperNavigate', function (string|array|null $key = null) {
            // First check if this is a navigate request at all
            if (!$this->hasHeader('HYPER-NAVIGATE')) {
                return false;
            }

            // If no specific key requested, return true for any navigate request
            if ($key === null) {
                return true;
            }

            // Get the navigate key(s) from header
            $navigateKey = $this->header('HYPER-NAVIGATE-KEY', '');

            if (empty($navigateKey)) {
                return false;
            }

            // Parse comma-separated keys
            $navigateKeys = array_map('trim', explode(',', $navigateKey));

            // Handle array of keys to check
            if (is_array($key)) {
                return !empty(array_intersect($key, $navigateKeys));
            }

            // Handle single key
            return in_array($key, $navigateKeys);
        });

        // Get the navigate key(s) from the request
        Request::macro('hyperNavigateKey', function () {
            $key = $this->header('HYPER-NAVIGATE-KEY', '');

            return empty($key) ? null : $key;
        });

        // Get navigate keys as array
        Request::macro('hyperNavigateKeys', function () {
            /** @phpstan-ignore method.notFound (macro defined above) */
            $key = $this->hyperNavigateKey();

            return $key ? array_map('trim', explode(',', $key)) : [];
        });
    }

    /**
     * Register Response facade macros for Hyper response building
     *
     * Extends Laravel's Response factory with a hyper() macro that retrieves
     * the singleton HyperResponse instance from the container. This provides
     * a fluent interface for building Server-Sent Events responses.
     *
     * The macro returns the same HyperResponse instance throughout a single
     * request, enabling event accumulation across multiple method calls before
     * the final response is sent.
     *
     *
     * @see \Dancycodes\Hyper\Http\HyperResponse
     */
    private function registerResponseMacros(): void
    {
        ResponseFactory::macro('hyper', function () {
            return app('hyper.response');
        });
    }

    /**
     * Enable automatic route discovery for controllers and views
     *
     * Initializes Hyper's route auto-discovery system when enabled in configuration
     * and routes are not cached. Discovery is skipped when routes are cached to
     * prevent runtime overhead in production environments.
     *
     * This method coordinates both controller-based and view-based route discovery,
     * allowing developers to define routes through class attributes or file structure
     * rather than explicit route definitions.
     *
     *
     * @see \Dancycodes\Hyper\Routing\Discovery\Discover
     */
    private function registerRouteDiscovery(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        if (!config('hyper.route_discovery.enabled', false)) {
            return;
        }

        $this
            ->registerRoutesForControllers()
            ->registerRoutesForViews();
    }

    /**
     * Register routes discovered from controller classes
     *
     * Scans configured directories for controller classes and automatically registers
     * routes based on PHP 8 attributes. Controllers can use attributes like #[Route],
     * #[Prefix], #[Where], and #[DoNotDiscover] to define routing behavior declaratively.
     *
     * The discovery process reads controller class metadata, extracts route definitions,
     * processes them through transformation pipelines, and registers them with Laravel's
     * Router. This eliminates the need for manual route definitions in route files.
     *
     *
     * @see \Dancycodes\Hyper\Routing\Discovery\DiscoverControllers
     */
    private function registerRoutesForControllers(): self
    {
        /** @var array<int, string> $directories */
        $directories = config('hyper.route_discovery.discover_controllers_in_directory', []);

        /** @var \Illuminate\Support\Collection<int, string> $directoryCollection */
        $directoryCollection = collect($directories);

        $directoryCollection->each(
            fn (string $directory) => \Dancycodes\Hyper\Routing\Discovery\Discover::controllers()->in($directory)
        );

        return $this;
    }

    /**
     * Register routes discovered from Blade view files
     *
     * Scans configured view directories and automatically creates routes based on file
     * structure and naming conventions. Each Blade file becomes a routable endpoint,
     * with the file path determining the URI structure.
     *
     * Supports optional URL prefixes through configuration, enabling organized routing
     * structures like '/admin' prefix for views in resources/views/admin directory.
     * This convention-over-configuration approach simplifies routing for content-heavy
     * applications and prototyping scenarios.
     *
     *
     * @see \Dancycodes\Hyper\Routing\Discovery\DiscoverViews
     */
    private function registerRoutesForViews(): self
    {
        /** @var array<int|string, array<int, string>|string> $config */
        $config = config('hyper.route_discovery.discover_views_in_directory', []);

        /** @var \Illuminate\Support\Collection<int|string, array<int, string>|string> $configCollection */
        $configCollection = collect($config);

        $configCollection->each(function (array|string $directories, int|string $prefix) {
            if (is_numeric($prefix)) {
                $prefix = '';
            }

            $directories = Arr::wrap($directories);

            foreach ($directories as $directory) {
                Route::prefix($prefix)->group(function () use ($directory, $prefix) {
                    // Pass the prefix to the DiscoverViews::in() method
                    \Dancycodes\Hyper\Routing\Discovery\Discover::views()->in($directory, $prefix);
                });
            }
        });

        return $this;
    }

    /**
     * Register custom base64 file validation rules
     *
     * Extends Laravel's Validator with custom rules for validating base64-encoded files
     * transmitted through Hyper signals. These rules mirror Laravel's standard file
     * validation rules but operate on base64 data instead of uploaded files.
     *
     * Registered validation rules:
     * - b64image: Validates base64 string represents a valid image
     * - b64file: Validates base64 string is a valid file
     * - b64dimensions: Validates image dimensions (width, height, min_width, etc.)
     * - b64max: Validates file size does not exceed maximum kilobytes
     * - b64min: Validates file size meets minimum kilobytes
     * - b64mimes: Validates file MIME type against allowed types
     * - b64size: Validates file size matches exact kilobytes
     *
     * All rules include validation message replacers that map to Laravel's existing
     * file validation messages, providing a consistent validation experience.
     *
     *
     * @see \Dancycodes\Hyper\Validation\HyperBase64Validator
     */
    private function registerBase64ValidationRules(): void
    {
        $validator = new \Dancycodes\Hyper\Validation\HyperBase64Validator;

        // Register all base64 validation rules
        // PHPStan doesn't recognize array callables are valid for Validator::extend()
        /** @phpstan-ignore argument.type */
        Validator::extend('b64image', [$validator, 'validateB64image']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64file', [$validator, 'validateB64file']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64dimensions', [$validator, 'validateB64dimensions']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64max', [$validator, 'validateB64max']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64min', [$validator, 'validateB64min']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64mimes', [$validator, 'validateB64mimes']);
        /** @phpstan-ignore argument.type */
        Validator::extend('b64size', [$validator, 'validateB64size']);

        // Map to Laravel's existing validation messages for perfect DX
        Validator::replacer('b64image', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.image', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
            ]);
        });

        Validator::replacer('b64file', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.file', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
            ]);
        });

        Validator::replacer('b64dimensions', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.dimensions', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
            ]);
        });

        Validator::replacer('b64max', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.max.file', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
                'max' => $parameters[0],
            ]);
        });

        Validator::replacer('b64min', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.min.file', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
                'min' => $parameters[0],
            ]);
        });

        Validator::replacer('b64mimes', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.mimes', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
                'values' => implode(', ', $parameters),
            ]);
        });

        Validator::replacer('b64size', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.size.file', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
                'size' => $parameters[0],
            ]);
        });
    }

    /**
     * Register the @signals Blade directive and its supporting service
     *
     * Binds the HyperSignalsDirective service as a singleton and registers the @signals
     * Blade directive for declarative signal initialization in templates.
     *
     * The @signals directive supports multiple syntax patterns:
     * - Literal variables: @signals($username, $email)
     * - Local signals: @signals($_tempValue)
     * - Locked signals: @signals($userId_)
     * - Spread syntax: @signals(...$user)
     * - Compact function: @signals(compact('var1', 'var2'))
     *
     * The directive parses these expressions and converts them to data-signals attributes
     * with proper JSON encoding. Locked signals (ending with '_') are automatically
     * stored in the session for tampering detection.
     *
     *
     * @see \Dancycodes\Hyper\Services\HyperSignalsDirective
     */
    protected function registerSignalsDirective(): void
    {
        $this->app->singleton('hyper.signals.directive', function ($app) {
            return new \Dancycodes\Hyper\Services\HyperSignalsDirective;
        });

        Blade::directive('signals', function ($expression) {
            if (empty($expression)) {
                return "<?php echo app('hyper.signals.directive')->render(); ?>";
            }

            $directive = app('hyper.signals.directive');
            $rewrittenExpression = $directive->parseAndRewriteExpression($expression);

            return "<?php echo app('hyper.signals.directive')->render({$rewrittenExpression}); ?>";
        });
    }

    /**
     * Register middleware to automatically convert redirects for Datastar requests
     *
     * Registers the ConvertRedirectsForDatastar middleware globally to intercept
     * standard Laravel redirect responses and convert them into Hyper-style SSE
     * responses for Datastar requests. This prevents the double-navigation issue
     * where flash data is consumed during fetch API's automatic redirect following.
     *
     * This middleware runs for ALL requests but only modifies responses for
     * Datastar requests that result in redirects, ensuring zero impact on normal
     * HTTP request/response cycles.
     */
    protected function registerRedirectConversionMiddleware(): void
    {
        /** @var \Illuminate\Contracts\Http\Kernel $kernel */
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        // Push middleware to the global middleware stack
        // This ensures it runs for all requests and can intercept responses
        $kernel->pushMiddleware(\Dancycodes\Hyper\Http\Middleware\ConvertRedirectsForDatastar::class);
    }

    /**
     * Register built-in icon providers with auto-discovery
     *
     * Automatically registers Heroicons provider if the blade-ui-kit/blade-heroicons
     * package is installed. This provides zero-configuration icon support out
     * of the box while allowing developers to add custom providers.
     *
     * Auto-discovery Logic:
     * 1. Check if Heroicons package is installed (file existence check)
     * 2. Register HeroiconsProvider as the default if found
     * 3. Developers can override or add more providers in their AppServiceProvider
     *
     * Custom Registration:
     * ```php
     * // In AppServiceProvider boot():
     * Html::iconProvider('fontawesome', FontAwesomeProvider::class);
     * Html::setDefaultIconProvider('fontawesome');
     * ```
     */
    protected function registerIconProviders(): void
    {
        $iconManager = $this->app->make(IconManager::class);

        // Auto-discover and register Heroicons if installed (blade-ui-kit/blade-heroicons)
        $heroiconsPath = base_path('vendor/blade-ui-kit/blade-heroicons');

        if (is_dir($heroiconsPath)) {
            $iconManager->register(
                'heroicons',
                \Dancycodes\Hyper\Html\Services\IconProviders\HeroiconsProvider::class
            );
        }

        // Auto-discover and register Feather Icons if installed
        $feathericonsPaths = [
            base_path('vendor/brunocfalcao/blade-feather-icons'),
            base_path('vendor/outhebox/blade-feather-icons'),
        ];

        foreach ($feathericonsPaths as $path) {
            if (is_dir($path)) {
                $iconManager->register(
                    'feathericons',
                    \Dancycodes\Hyper\Html\Services\IconProviders\FeathericonsProvider::class
                );

                break; // Only register once
            }
        }

        // Future: Add auto-discovery for other popular icon libraries
        // - Font Awesome (owenvoke/blade-fontawesome)
        // - Bootstrap Icons (davidhsianturi/blade-bootstrap-icons)
    }
}
