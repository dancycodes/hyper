<?php

namespace Dancycodes\Hyper\Tests\Unit\Routing\Attributes;

use Dancycodes\Hyper\Routing\Attributes\DiscoveryAttribute;
use Dancycodes\Hyper\Routing\Attributes\Where;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Test Where Attribute
 *
 * @see TESTING.md - File 18: WhereAttribute Tests
 * Status: ✅ COMPLETE - 8 test methods
 */
class WhereAttributeTest extends TestCase
{
    public static $latestResponse;

    /** @test */
    public function test_where_attribute_implements_discovery_attribute()
    {
        $where = new Where('id', Where::numeric);

        $this->assertInstanceOf(DiscoveryAttribute::class, $where);
    }

    /** @test */
    public function test_where_attribute_stores_param_and_constraint()
    {
        $where = new Where('slug', '[a-z-]+');

        $this->assertEquals('slug', $where->param);
        $this->assertEquals('[a-z-]+', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_alpha_constant()
    {
        $where = new Where('name', Where::alpha);

        $this->assertEquals('[a-zA-Z]+', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_numeric_constant()
    {
        $where = new Where('id', Where::numeric);

        $this->assertEquals('[0-9]+', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_alphanumeric_constant()
    {
        $where = new Where('code', Where::alphanumeric);

        $this->assertEquals('[a-zA-Z0-9]+', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_uuid_constant()
    {
        $where = new Where('uuid', Where::uuid);

        $this->assertEquals('[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_with_custom_regex()
    {
        $where = new Where('date', '\d{4}-\d{2}-\d{2}');

        $this->assertEquals('date', $where->param);
        $this->assertEquals('\d{4}-\d{2}-\d{2}', $where->constraint);
    }

    /** @test */
    public function test_where_attribute_can_be_used_as_attribute()
    {
        // Verify that Where has the Attribute annotation
        $reflection = new \ReflectionClass(Where::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
    }
}
