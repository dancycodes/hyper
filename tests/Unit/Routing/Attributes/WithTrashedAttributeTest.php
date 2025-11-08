<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\Attributes;

use Dancycodes\Hyper\Routing\Attributes\DiscoveryAttribute;
use Dancycodes\Hyper\Routing\Attributes\WithTrashed;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test WithTrashed Attribute
 *
 * @see TESTING.md - File 19: WithTrashedAttribute Tests
 * Status: ✅ COMPLETE - 4 test methods
 */
class WithTrashedAttributeTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_with_trashed_attribute_implements_discovery_attribute()
    {
        $withTrashed = new WithTrashed;

        $this->assertInstanceOf(DiscoveryAttribute::class, $withTrashed);
    }

    /** @test */
    public function test_with_trashed_attribute_can_be_instantiated()
    {
        $withTrashed = new WithTrashed;

        $this->assertInstanceOf(WithTrashed::class, $withTrashed);
    }

    /** @test */
    public function test_with_trashed_attribute_has_no_properties()
    {
        $withTrashed = new WithTrashed;

        $reflection = new \ReflectionClass($withTrashed);
        $properties = $reflection->getProperties();

        $this->assertEmpty($properties);
    }

    /** @test */
    public function test_with_trashed_attribute_can_be_used_as_attribute()
    {
        // Verify that WithTrashed has the Attribute annotation
        $reflection = new \ReflectionClass(WithTrashed::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
    }
}
