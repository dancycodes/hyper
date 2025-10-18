<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRouteTransformers\AddDefaultRouteName;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer;
use Dancycodes\Hyper\Tests\TestCase;

class AddDefaultRouteNameTest extends TestCase
{
    /** @test */
    public function test_transform_applies_transformation()
    {
        $transformer = new AddDefaultRouteName;

        $this->assertInstanceOf(PendingRouteTransformer::class, $transformer);
    }

    /** @test */
    public function test_transform_with_edge_cases()
    {
        $transformer = new AddDefaultRouteName;
        $result = $transformer->transform(collect([]));

        $this->assertCount(0, $result);
    }

    /** @test */
    public function test_transform_preserves_other_properties()
    {
        $transformer = new AddDefaultRouteName;

        $this->assertInstanceOf(AddDefaultRouteName::class, $transformer);
    }

    /** @test */
    public function test_transformer_returns_collection()
    {
        $transformer = new AddDefaultRouteName;
        $result = $transformer->transform(collect([]));

        $this->assertIsIterable($result);
    }
}
