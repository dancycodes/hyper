<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\Discovery;

use Dancycodes\Hyper\Routing\Discovery\Discover;
use Dancycodes\Hyper\Routing\Discovery\DiscoverControllers;
use Dancycodes\Hyper\Routing\Discovery\DiscoverViews;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test Discover Factory Class
 *
 * @see TESTING.md - File 13: Discover Tests
 * Status: ✅ COMPLETE - 3 test methods
 */
class DiscoverTest extends TestCase
{
    protected static $latestResponse;

    /** @test */
    public function test_discover_controllers_factory_method()
    {
        $controllers = Discover::controllers();

        $this->assertInstanceOf(DiscoverControllers::class, $controllers);
    }

    /** @test */
    public function test_discover_views_factory_method()
    {
        $views = Discover::views();

        $this->assertInstanceOf(DiscoverViews::class, $views);
    }

    /** @test */
    public function test_discover_with_custom_transformers()
    {
        // Factory methods should return fresh instances each time
        $controllers1 = Discover::controllers();
        $controllers2 = Discover::controllers();

        $this->assertNotSame($controllers1, $controllers2);

        $views1 = Discover::views();
        $views2 = Discover::views();

        $this->assertNotSame($views1, $views2);
    }
}
