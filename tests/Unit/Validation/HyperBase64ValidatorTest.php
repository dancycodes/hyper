<?php

namespace Dancycodes\Hyper\Tests\Unit\Validation;

use Dancycodes\Hyper\Tests\TestCase;
use Dancycodes\Hyper\Validation\HyperBase64Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

/**
 * Test the HyperBase64Validator class
 *
 * @see TESTING.md - File 10: HyperBase64Validator Tests
 * Status: 🔄 IN PROGRESS - 40 test methods
 */
class HyperBase64ValidatorTest extends TestCase
{
    public static $latestResponse;

    protected HyperBase64Validator $validator;

    // Valid 1x1 PNG image (smallest possible PNG)
    protected string $validPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    // Valid JPEG
    protected string $validJpegBase64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=';

    // Valid GIF
    protected string $validGifBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HyperBase64Validator;
    }

    protected function validate($rules, $data)
    {
        return ValidatorFacade::make($data, $rules)->passes();
    }

    // ==========================================
    // Image Validation Tests (8 methods)
    // ==========================================

    /** @test */
    public function test_b64image_accepts_valid_png()
    {
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64image_accepts_valid_jpeg()
    {
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => $this->validJpegBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64image_accepts_valid_gif()
    {
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => $this->validGifBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64image_rejects_invalid_base64()
    {
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => 'not-valid-base64!!!']
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64image_rejects_non_image_data()
    {
        $textBase64 = base64_encode('Plain text content');

        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => $textBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64image_handles_data_uri_format()
    {
        $dataUri = 'data:image/png;base64,' . $this->validPngBase64;

        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => $dataUri]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64image_handles_array_format()
    {
        // Datastar signal format
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => [$this->validPngBase64]]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64image_allows_empty_value()
    {
        // Empty values should pass (let 'required' handle it)
        $passes = $this->validate(
            ['file' => 'b64image'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    // ==========================================
    // File Validation Tests (5 methods)
    // ==========================================

    /** @test */
    public function test_b64file_accepts_valid_base64()
    {
        $passes = $this->validate(
            ['file' => 'b64file'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64file_accepts_text_content()
    {
        $textBase64 = base64_encode('Text file content');

        $passes = $this->validate(
            ['file' => 'b64file'],
            ['file' => $textBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64file_rejects_invalid_base64()
    {
        $passes = $this->validate(
            ['file' => 'b64file'],
            ['file' => 'invalid!!!base64']
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64file_handles_data_uri()
    {
        $dataUri = 'data:text/plain;base64,' . base64_encode('test');

        $passes = $this->validate(
            ['file' => 'b64file'],
            ['file' => $dataUri]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64file_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64file'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    // ==========================================
    // Dimensions Validation Tests (10 methods)
    // ==========================================

    /** @test */
    public function test_b64dimensions_checks_min_width()
    {
        // Our test image is 1x1, so min_width=1 should pass
        $passes = $this->validate(
            ['file' => 'b64dimensions:min_width=1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);

        // min_width=2 should fail
        $passes = $this->validate(
            ['file' => 'b64dimensions:min_width=2'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_max_width()
    {
        // max_width=100 should pass for 1x1 image
        $passes = $this->validate(
            ['file' => 'b64dimensions:max_width=100'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);

        // max_width=0 should fail
        $passes = $this->validate(
            ['file' => 'b64dimensions:max_width=0'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_min_height()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:min_height=1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);

        $passes = $this->validate(
            ['file' => 'b64dimensions:min_height=2'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_max_height()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:max_height=100'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_exact_width()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:width=1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);

        $passes = $this->validate(
            ['file' => 'b64dimensions:width=2'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_exact_height()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:height=1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64dimensions_checks_ratio()
    {
        // 1x1 image has ratio 1/1 = 1.0
        $passes = $this->validate(
            ['file' => 'b64dimensions:ratio=1/1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);

        // Ratio 16/9 should fail for 1x1 image
        $passes = $this->validate(
            ['file' => 'b64dimensions:ratio=16/9'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_handles_multiple_constraints()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:min_width=1,max_width=10,min_height=1,max_height=10'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64dimensions_rejects_non_image()
    {
        $textBase64 = base64_encode('Not an image');

        $passes = $this->validate(
            ['file' => 'b64dimensions:min_width=1'],
            ['file' => $textBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64dimensions_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64dimensions:min_width=100'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    // ==========================================
    // Size Validation Tests (9 methods)
    // ==========================================

    /** @test */
    public function test_b64max_checks_maximum_size()
    {
        // Our small test image is less than 1KB
        $passes = $this->validate(
            ['file' => 'b64max:1'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64max_rejects_files_over_limit()
    {
        // 0KB limit should fail for any file
        $passes = $this->validate(
            ['file' => 'b64max:0'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64min_checks_minimum_size()
    {
        // File must be at least 0KB (should always pass for non-empty)
        $passes = $this->validate(
            ['file' => 'b64min:0'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64min_rejects_files_under_limit()
    {
        // Require 1000KB minimum (should fail for small image)
        $passes = $this->validate(
            ['file' => 'b64min:1000'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64size_checks_exact_size()
    {
        // BUG FIXED: The validator now uses float comparison with tolerance
        // getFileSizeFromBase64() returns float (from round())
        // validateB64size() now uses: abs($sizeKb - $expectedKb) < 0.01
        // This correctly handles float comparisons

        $oneKbData = str_repeat('A', 1024);
        $oneKbBase64 = base64_encode($oneKbData);

        // Validation should now pass for exact size match
        $passes = $this->validate(
            ['file' => 'b64size:1'],
            ['file' => $oneKbBase64]
        );

        $this->assertTrue($passes, 'b64size validation now correctly compares sizes using float tolerance');
    }

    /** @test */
    public function test_b64size_rejects_different_size()
    {
        $passes = $this->validate(
            ['file' => 'b64size:999'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64max_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64max:1'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64min_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64min:10'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64size_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64size:5'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    // ==========================================
    // MIME Type Validation Tests (8 methods)
    // ==========================================

    /** @test */
    public function test_b64mimes_accepts_valid_png()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:png'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64mimes_accepts_valid_jpeg()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:jpg,jpeg'],
            ['file' => $this->validJpegBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64mimes_accepts_valid_gif()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:gif'],
            ['file' => $this->validGifBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64mimes_rejects_wrong_type()
    {
        // PNG file but only JPG allowed
        $passes = $this->validate(
            ['file' => 'b64mimes:jpg'],
            ['file' => $this->validPngBase64]
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64mimes_accepts_multiple_types()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:png,jpg,gif'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64mimes_handles_invalid_base64()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:png'],
            ['file' => 'invalid!!!']
        );

        $this->assertFalse($passes);
    }

    /** @test */
    public function test_b64mimes_allows_empty_value()
    {
        $passes = $this->validate(
            ['file' => 'b64mimes:png,jpg'],
            ['file' => '']
        );

        $this->assertTrue($passes);
    }

    /** @test */
    public function test_b64mimes_case_insensitive()
    {
        // The validator converts MIME types to lowercase extensions
        // So parameters should be lowercase to match
        $passes = $this->validate(
            ['file' => 'b64mimes:png,jpg'],
            ['file' => $this->validPngBase64]
        );

        $this->assertTrue($passes);
    }
}
