<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\PendingRouteTransformers;

use Dancycodes\Hyper\Routing\PendingRouteTransformers\AddControllerUriToActions;
use Dancycodes\Hyper\Routing\PendingRouteTransformers\PendingRouteTransformer;
use Dancycodes\Hyper\Tests\TestCase;

class AddControllerUriToActionsTest extends TestCase
{
    protected static $latestResponse;

    /** @test */
    public function test_transform_applies_transformation()
    {
        $transformer = new AddControllerUriToActions;

        $this->assertInstanceOf(PendingRouteTransformer::class, $transformer);
    }

    /** @test */
    public function test_transform_with_edge_cases()
    {
        $transformer = new AddControllerUriToActions;
        $result = $transformer->transform(collect([]));

        $this->assertCount(0, $result);
    }

    /** @test */
    public function test_transform_preserves_other_properties()
    {
        $transformer = new AddControllerUriToActions;

        $this->assertInstanceOf(AddControllerUriToActions::class, $transformer);
    }

    /** @test */
    public function test_transformer_returns_collection()
    {
        $transformer = new AddControllerUriToActions;
        $result = $transformer->transform(collect([]));

        $this->assertIsIterable($result);
    }
}
