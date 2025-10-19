<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRoutes;

use Dancycodes\Hyper\Routing\Attributes\Where;
use Dancycodes\Hyper\Routing\PendingRoutes\PendingRouteAction;
use Dancycodes\Hyper\Tests\TestCase;
use ReflectionMethod;

/**
 * Test PendingRouteAction Class
 *
 * @see TESTING.md - File 23: PendingRouteAction Tests
 * Status: ✅ COMPLETE - 15 test methods
 */
class PendingRouteActionTest extends TestCase
{
    protected static $latestResponse;

    private function getTestMethod(): ReflectionMethod
    {
        return new ReflectionMethod($this, 'setUp');
    }

    /** @test */
    public function test_pending_route_action_stores_method()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertSame($method, $action->method);
    }

    /** @test */
    public function test_pending_route_action_generates_uri()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertIsString($action->uri);
    }

    /** @test */
    public function test_pending_route_action_discovers_http_methods()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertIsArray($action->methods);
        $this->assertNotEmpty($action->methods);
    }

    /** @test */
    public function test_pending_route_action_stores_action_array()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertIsArray($action->action);
        $this->assertCount(2, $action->action);
    }

    /** @test */
    public function test_add_where_method()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $where = new Where('id', Where::numeric);
        $result = $action->addWhere($where);

        $this->assertSame($action, $result);
        $this->assertArrayHasKey('id', $action->wheres);
        $this->assertEquals('[0-9]+', $action->wheres['id']);
    }

    /** @test */
    public function test_add_middleware_with_string()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $result = $action->addMiddleware('auth');

        $this->assertSame($action, $result);
        $this->assertContains('auth', $action->middleware);
    }

    /** @test */
    public function test_add_middleware_with_array()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $result = $action->addMiddleware(['auth', 'verified']);

        $this->assertSame($action, $result);
        $this->assertContains('auth', $action->middleware);
        $this->assertContains('verified', $action->middleware);
    }

    /** @test */
    public function test_add_middleware_removes_duplicates()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $action->addMiddleware('auth');
        $action->addMiddleware('auth'); // Duplicate

        $this->assertCount(1, $action->middleware);
    }

    /** @test */
    public function test_action_method_returns_controller_action()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $result = $action->action();

        $this->assertTrue(is_array($result) || is_string($result));
    }

    /** @test */
    public function test_model_parameters_method()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $params = $action->modelParameters();

        $this->assertIsIterable($params);
    }

    /** @test */
    public function test_relative_uri_method()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $uri = $action->relativeUri();

        $this->assertIsString($uri);
    }

    /** @test */
    public function test_get_route_attribute()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $attribute = $action->getRouteAttribute();

        // Should be null since setUp method has no Route attribute
        $this->assertNull($attribute);
    }

    /** @test */
    public function test_pending_route_action_default_middleware()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertEquals([], $action->middleware);
    }

    /** @test */
    public function test_pending_route_action_default_wheres()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertEquals([], $action->wheres);
    }

    /** @test */
    public function test_pending_route_action_default_name()
    {
        $method = $this->getTestMethod();
        $action = new PendingRouteAction($method, static::class);

        $this->assertNull($action->name);
    }
}
