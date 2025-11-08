<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRoutes;

use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRoute;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Collection;
use ReflectionClass;
use SplFileInfo;

/**
 * Test PendingRoute Class
 *
 * @see TESTING.md - File 22: PendingRoute Tests
 * Status: ✅ COMPLETE - 10 test methods
 */
class PendingRouteTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_pending_route_stores_properties()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            static::class,
            $actions
        );

        $this->assertSame($fileInfo, $pendingRoute->fileInfo);
        $this->assertSame($class, $pendingRoute->class);
        $this->assertEquals('/test', $pendingRoute->uri);
        $this->assertEquals(static::class, $pendingRoute->fullyQualifiedClassName);
        $this->assertInstanceOf(Collection::class, $pendingRoute->actions);
    }

    /** @test */
    public function test_namespace_method()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            'App\\Http\\Controllers\\UserController',
            $actions
        );

        $this->assertEquals('App\\Http\\Controllers', $pendingRoute->namespace());
    }

    /** @test */
    public function test_short_controller_name_method()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            'App\\Http\\Controllers\\UserController',
            $actions
        );

        $this->assertEquals('User', $pendingRoute->shortControllerName());
    }

    /** @test */
    public function test_child_namespace_method()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            'App\\Http\\Controllers\\UserController',
            $actions
        );

        $this->assertEquals('App\\Http\\Controllers\\User', $pendingRoute->childNamespace());
    }

    /** @test */
    public function test_get_route_attribute_returns_null_when_not_present()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            static::class,
            $actions
        );

        $this->assertNull($pendingRoute->getRouteAttribute());
    }

    /** @test */
    public function test_get_attribute_returns_null_when_not_present()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            static::class,
            $actions
        );

        $this->assertNull($pendingRoute->getAttribute(Route::class));
    }

    /** @test */
    public function test_pending_route_with_actions_collection()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);

        // Create mock actions
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/users',
            'App\\Http\\Controllers\\UserController',
            $actions
        );

        $this->assertInstanceOf(Collection::class, $pendingRoute->actions);
    }

    /** @test */
    public function test_namespace_with_single_namespace()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            'UserController',
            $actions
        );

        // When there's no backslash, beforeLast returns the whole string
        $this->assertEquals('UserController', $pendingRoute->namespace());
    }

    /** @test */
    public function test_short_controller_name_without_controller_suffix()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/test',
            'App\\Http\\User',
            $actions
        );

        $this->assertEquals('User', $pendingRoute->shortControllerName());
    }

    /** @test */
    public function test_pending_route_fully_qualified_class_name()
    {
        $fileInfo = new SplFileInfo(__FILE__);
        $class = new ReflectionClass($this);
        $actions = collect([]);
        $fqcn = 'App\\Http\\Controllers\\Admin\\DashboardController';

        $pendingRoute = new PendingRoute(
            $fileInfo,
            $class,
            '/admin/dashboard',
            $fqcn,
            $actions
        );

        $this->assertEquals($fqcn, $pendingRoute->fullyQualifiedClassName);
    }
}
