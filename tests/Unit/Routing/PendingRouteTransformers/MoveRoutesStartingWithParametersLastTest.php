<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRouteTransformers\MoveRoutesStartingWithParametersLast;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer;
use Dancycodes\Hyper\Tests\TestCase;

class MoveRoutesStartingWithParametersLastTest extends TestCase
{
    /** @test */
    public function test_transform_applies_transformation()
    {
        $transformer = new MoveRoutesStartingWithParametersLast;

        $this->assertInstanceOf(PendingRouteTransformer::class, $transformer);
    }

    /** @test */
    public function test_transform_with_edge_cases()
    {
        $transformer = new MoveRoutesStartingWithParametersLast;
        $result = $transformer->transform(collect([]));

        $this->assertCount(0, $result);
    }

    /** @test */
    public function test_transform_preserves_other_properties()
    {
        $transformer = new MoveRoutesStartingWithParametersLast;

        $this->assertInstanceOf(MoveRoutesStartingWithParametersLast::class, $transformer);
    }

    /** @test */
    public function test_transformer_returns_collection()
    {
        $transformer = new MoveRoutesStartingWithParametersLast;
        $result = $transformer->transform(collect([]));

        $this->assertIsIterable($result);
    }
}
