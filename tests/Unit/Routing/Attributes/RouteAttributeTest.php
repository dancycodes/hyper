<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\Attributes;

use Dancycodes\Hyper\Routing\Attributes\DiscoveryAttribute;
use Dancycodes\Hyper\Routing\Attributes\Route;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test Route Attribute
 *
 * @see TESTING.md - File 16: RouteAttribute Tests
 * Status: ✅ COMPLETE - 12 test methods
 */
class RouteAttributeTest extends TestCase
{
    protected static $latestResponse;

    /** @test */
    public function test_route_attribute_implements_discovery_attribute()
    {
        $route = new Route;

        $this->assertInstanceOf(DiscoveryAttribute::class, $route);
    }

    /** @test */
    public function test_route_attribute_with_single_method()
    {
        $route = new Route('GET');

        $this->assertEquals(['GET'], $route->methods);
    }

    /** @test */
    public function test_route_attribute_with_multiple_methods()
    {
        $route = new Route(['GET', 'POST']);

        $this->assertEquals(['GET', 'POST'], $route->methods);
    }

    /** @test */
    public function test_route_attribute_normalizes_methods_to_uppercase()
    {
        $route = new Route(['get', 'post', 'Put']);

        $this->assertEquals(['GET', 'POST', 'PUT'], $route->methods);
    }

    /** @test */
    public function test_route_attribute_with_uri()
    {
        $route = new Route('GET', '/users');

        $this->assertEquals('/users', $route->uri);
    }

    /** @test */
    public function test_route_attribute_with_full_uri()
    {
        $route = new Route('GET', fullUri: '/api/users');

        $this->assertEquals('/api/users', $route->fullUri);
    }

    /** @test */
    public function test_route_attribute_with_name()
    {
        $route = new Route('GET', name: 'users.index');

        $this->assertEquals('users.index', $route->name);
    }

    /** @test */
    public function test_route_attribute_with_single_middleware()
    {
        $route = new Route('GET', middleware: 'auth');

        $this->assertEquals(['auth'], $route->middleware);
    }

    /** @test */
    public function test_route_attribute_with_multiple_middleware()
    {
        $route = new Route('GET', middleware: ['auth', 'verified']);

        $this->assertEquals(['auth', 'verified'], $route->middleware);
    }

    /** @test */
    public function test_route_attribute_with_domain()
    {
        $route = new Route('GET', domain: 'api.example.com');

        $this->assertEquals('api.example.com', $route->domain);
    }

    /** @test */
    public function test_route_attribute_with_trashed()
    {
        $route = new Route('GET', withTrashed: true);

        $this->assertTrue($route->withTrashed);
    }

    /** @test */
    public function test_route_attribute_filters_invalid_http_methods()
    {
        $route = new Route(['GET', 'INVALID', 'POST']);

        // INVALID should be filtered out
        $this->assertContains('GET', $route->methods);
        $this->assertContains('POST', $route->methods);
        $this->assertNotContains('INVALID', $route->methods);
    }

    /** @test */
    public function test_route_attribute_default_values()
    {
        $route = new Route;

        $this->assertEquals([], $route->methods);
        $this->assertNull($route->uri);
        $this->assertNull($route->fullUri);
        $this->assertNull($route->name);
        $this->assertEquals([], $route->middleware);
        $this->assertNull($route->domain);
        $this->assertFalse($route->withTrashed);
    }
}
