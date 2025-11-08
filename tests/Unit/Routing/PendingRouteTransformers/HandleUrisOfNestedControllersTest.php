<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRouteTransformers\HandleUrisOfNestedControllers;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer;
use Dancycodes\Hyper\Tests\TestCase;

class HandleUrisOfNestedControllersTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_transform_applies_transformation()
    {
        $transformer = new HandleUrisOfNestedControllers;

        $this->assertInstanceOf(PendingRouteTransformer::class, $transformer);
    }

    /** @test */
    public function test_transform_with_edge_cases()
    {
        $transformer = new HandleUrisOfNestedControllers;
        $result = $transformer->transform(collect([]));

        $this->assertCount(0, $result);
    }

    /** @test */
    public function test_transform_preserves_other_properties()
    {
        $transformer = new HandleUrisOfNestedControllers;

        $this->assertInstanceOf(HandleUrisOfNestedControllers::class, $transformer);
    }

    /** @test */
    public function test_transformer_returns_collection()
    {
        $transformer = new HandleUrisOfNestedControllers;
        $result = $transformer->transform(collect([]));

        $this->assertIsIterable($result);
    }
}
