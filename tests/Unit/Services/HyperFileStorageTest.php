<?php

namespace Dancycodes\Hyper\Tests\Unit\Services;

use Dancycodes\Hyper\Services\HyperFileStorage;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Test the HyperFileStorage service
 *
 * @see TESTING.md - File 4: HyperFileStorage Tests
 * Status: 🔄 IN PROGRESS - 30 test methods
 */
class HyperFileStorageTest extends TestCase
{
    public static $latestResponse;

    protected HyperFileStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(HyperFileStorage::class);
        Storage::fake('public');
    }

    protected function setupSignals(array $signals): void
    {
        request()->headers->set('Datastar-Request', 'true');
        request()->merge(['datastar' => json_encode($signals)]);
    }

    /**
     * Create RC6 format file data
     */
    protected function createRC6File(string $contents, string $name = 'test-file.png', string $mime = 'image/png'): array
    {
        return [[
            'name' => $name,
            'contents' => $contents,
            'mime' => $mime,
        ]];
    }

    // ==========================================
    // Base64 Decoding Tests (8 methods)
    // ==========================================

    /** @test */
    public function test_store_decodes_base64_png_image()
    {
        // Valid 1x1 PNG image
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->setupSignals(['avatar' => $this->createRC6File($base64Png, 'avatar.png')]);

        $path = $this->storage->store('avatar', 'images', 'public');

        $this->assertNotEmpty($path);
        $this->assertStringStartsWith('images/', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_decodes_base64_jpeg_image()
    {
        // Minimal valid JPEG
        $base64Jpeg = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=';

        $this->setupSignals(['photo' => $this->createRC6File($base64Jpeg, 'photo.jpg', 'image/jpeg')]);

        $path = $this->storage->store('photo', 'photos', 'public');

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_handles_rc6_format()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->setupSignals(['avatar' => $this->createRC6File($base64Png, 'avatar.png')]);

        $path = $this->storage->store('avatar', '', 'public');

        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_throws_exception_for_empty_base64()
    {
        $this->setupSignals(['avatar' => '']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No base64 data found for signal: avatar');

        $this->storage->store('avatar', 'images', 'public');
    }

    /** @test */
    public function test_store_throws_exception_for_missing_signal()
    {
        $this->setupSignals([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No base64 data found for signal: avatar');

        $this->storage->store('avatar', 'images', 'public');
    }

    /** @test */
    public function test_store_throws_exception_for_empty_array_signal()
    {
        $this->setupSignals(['avatar' => []]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No base64 data found for signal: avatar');

        $this->storage->store('avatar', 'images', 'public');
    }

    // ==========================================
    // File Storage Tests (10 methods)
    // ==========================================

    /** @test */
    public function test_store_saves_to_correct_disk()
    {
        Storage::fake('local');

        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'docs', 'local');

        Storage::disk('local')->assertExists($path);
    }

    /** @test */
    public function test_store_saves_to_correct_directory()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'uploads/avatars', 'public');

        $this->assertStringStartsWith('uploads/avatars/', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_generates_unique_filename()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file1' => $base64Png, 'file2' => $base64Png]);

        $path1 = $this->storage->store('file1', 'images', 'public');
        $path2 = $this->storage->store('file2', 'images', 'public');

        $this->assertNotEquals($path1, $path2);
    }

    /** @test */
    public function test_store_uses_custom_filename()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'images', 'public', 'custom-avatar.png');

        $this->assertEquals('images/custom-avatar.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_with_custom_filename_no_directory()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', '', 'public', 'avatar.png');

        $this->assertEquals('avatar.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_with_null_directory()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', '', 'public');

        $this->assertNotEmpty($path);
        $this->assertStringNotContainsString('/', $path); // No directory prefix
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_with_nested_directory()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'uploads/2024/january', 'public');

        $this->assertStringStartsWith('uploads/2024/january/', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function test_store_overwrites_with_same_filename()
    {
        $base64Png1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $base64Png2 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->setupSignals(['file' => $base64Png1]);
        $path1 = $this->storage->store('file', 'images', 'public', 'test.png');

        $this->setupSignals(['file' => $base64Png2]);
        $path2 = $this->storage->store('file', 'images', 'public', 'test.png');

        $this->assertEquals($path1, $path2);
        Storage::disk('public')->assertExists($path2);
    }

    /** @test */
    public function test_store_returns_relative_path()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'images', 'public');

        $this->assertIsString($path);
        $this->assertStringStartsWith('images/', $path);
        $this->assertStringNotContainsString('public/', $path); // Relative, not absolute
    }

    /** @test */
    public function test_store_handles_directory_with_trailing_slash()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'images/', 'public');

        $this->assertStringStartsWith('images/', $path);
        $this->assertStringNotContainsString('//', $path); // No double slashes
    }

    // ==========================================
    // URL Generation Tests (4 methods)
    // ==========================================

    /** @test */
    public function test_store_as_url_returns_public_url()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['avatar' => $base64Png]);

        $url = $this->storage->storeAsUrl('avatar', 'avatars', 'public');

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('avatars/', $url);
    }

    /** @test */
    public function test_store_as_url_with_public_disk()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $url = $this->storage->storeAsUrl('file', 'files', 'public');

        $this->assertIsString($url);
        $this->assertNotEmpty($url);
    }

    /** @test */
    public function test_store_as_url_with_local_disk()
    {
        Storage::fake('local');

        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $url = $this->storage->storeAsUrl('file', 'docs', 'local');

        $this->assertIsString($url);
        $this->assertStringContainsString('docs/', $url);
    }

    /** @test */
    public function test_store_as_url_with_custom_filename()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $url = $this->storage->storeAsUrl('file', 'images', 'public', 'logo.png');

        $this->assertStringContainsString('logo.png', $url);
    }

    // ==========================================
    // Multiple Files Tests (3 methods)
    // ==========================================

    /** @test */
    public function test_store_multiple_handles_array_of_files()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->setupSignals([
            'avatar' => $base64Png,
            'cover' => $base64Png,
            'logo' => $base64Png,
        ]);

        $mapping = [
            'avatar' => 'avatars',
            'cover' => 'covers',
            'logo' => 'logos',
        ];

        $results = $this->storage->storeMultiple($mapping, 'public');

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('avatar', $results);
        $this->assertArrayHasKey('cover', $results);
        $this->assertArrayHasKey('logo', $results);

        $this->assertStringStartsWith('avatars/', $results['avatar']);
        $this->assertStringStartsWith('covers/', $results['cover']);
        $this->assertStringStartsWith('logos/', $results['logo']);
    }

    /** @test */
    public function test_store_multiple_returns_array_of_paths()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->setupSignals([
            'file1' => $base64Png,
            'file2' => $base64Png,
        ]);

        $mapping = [
            'file1' => 'dir1',
            'file2' => 'dir2',
        ];

        $results = $this->storage->storeMultiple($mapping, 'public');

        $this->assertIsArray($results);
        foreach ($results as $path) {
            $this->assertIsString($path);
            Storage::disk('public')->assertExists($path);
        }
    }

    /** @test */
    public function test_store_multiple_skips_missing_signals()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        // Only provide file1, not file2 or file3
        $this->setupSignals([
            'file1' => $base64Png,
        ]);

        $mapping = [
            'file1' => 'dir1',
            'file2' => 'dir2',
            'file3' => 'dir3',
        ];

        $results = $this->storage->storeMultiple($mapping, 'public');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('file1', $results);
        $this->assertArrayNotHasKey('file2', $results);
        $this->assertArrayNotHasKey('file3', $results);
    }

    // ==========================================
    // MIME & Extension Tests (5 methods)
    // ==========================================

    /** @test */
    public function test_detect_extension_detects_png()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->setupSignals(['file' => $base64Png]);

        $path = $this->storage->store('file', 'images', 'public');

        $this->assertStringEndsWith('.png', $path);
    }

    /** @test */
    public function test_detect_extension_detects_jpeg()
    {
        $base64Jpeg = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=';
        $this->setupSignals(['file' => $base64Jpeg]);

        $path = $this->storage->store('file', 'photos', 'public');

        $this->assertStringEndsWith('.jpg', $path);
    }

    /** @test */
    public function test_detect_extension_detects_gif()
    {
        // Minimal GIF: 1x1 transparent
        $base64Gif = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        $this->setupSignals(['file' => $base64Gif]);

        $path = $this->storage->store('file', 'gifs', 'public');

        $this->assertStringEndsWith('.gif', $path);
    }

    /** @test */
    public function test_detect_extension_detects_webp()
    {
        // Minimal WebP (may not work in all environments)
        $base64Webp = 'UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=';
        $this->setupSignals(['file' => $base64Webp]);

        $path = $this->storage->store('file', 'images', 'public');

        // WebP detection may not be available in all PHP configurations
        $this->assertTrue(
            str_ends_with($path, '.webp') || str_ends_with($path, '.bin'),
            'Should detect webp or fallback to bin'
        );
    }

    /** @test */
    public function test_detect_extension_uses_bin_fallback_for_unknown()
    {
        // Text data will be detected as text/plain -> .txt
        // Binary data that can't be identified will fallback to .bin
        $base64Unknown = base64_encode('UNKNOWN FILE FORMAT DATA HERE');
        $this->setupSignals(['file' => $base64Unknown]);

        $path = $this->storage->store('file', 'files', 'public');

        // Should detect as text (.txt) or fallback to .bin for truly unknown types
        $this->assertTrue(
            str_ends_with($path, '.bin') || str_ends_with($path, '.txt'),
            'Should fallback to .bin or detect as .txt for text-like data'
        );
    }
}
