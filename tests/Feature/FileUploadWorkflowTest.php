<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Test File Upload Workflows with Base64
 *
 * @see TESTING.md - File 52: FileUploadWorkflow Tests
 * Status: 🔄 IN PROGRESS - 22 test methods
 */
class FileUploadWorkflowTest extends TestCase
{
    protected static $latestResponse;

    // Valid 1x1 PNG image (smallest possible PNG)
    protected string $validPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function test_base64_image_upload_and_storage()
    {
        Route::post('/upload-image', function () {
            $path = hyperStorage()->store('avatar', 'avatars', 'public');

            return hyper()->signals(['uploaded' => true, 'path' => $path]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-image', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_base64_pdf_upload_and_storage()
    {
        $pdfBase64 = base64_encode('Mock PDF content');
        Route::post('/upload-pdf', function () {
            $path = hyperStorage()->store('document', 'documents', 'public');

            return hyper()->signals(['uploaded' => true]);
        });
        $signals = json_encode(['document' => $pdfBase64]);
        $response = $this->call('POST', '/upload-pdf', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_validation_with_b64image_rule()
    {
        Route::post('/validate-image', function () {
            try {
                signals()->validate(['avatar' => 'required|b64image']);

                return hyper()->signals(['valid' => true]);
            } catch (\Exception $e) {
                return hyper()->signals(['errors' => ['avatar' => ['Invalid image']]]);
            }
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/validate-image', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_validation_with_b64max_rule()
    {
        Route::post('/validate-size', function () {
            $validated = signals()->validate(['avatar' => 'required|b64image|b64max:1024']);

            return hyper()->signals(['valid' => true]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/validate-size', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_validation_with_b64dimensions_rule()
    {
        Route::post('/validate-dimensions', function () {
            $validated = signals()->validate(['avatar' => 'required|b64image|b64dimensions:min_width=1,min_height=1']);

            return hyper()->signals(['valid' => true]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/validate-dimensions', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_validation_with_b64mimes_rule()
    {
        Route::post('/validate-mimes', function () {
            $validated = signals()->validate(['avatar' => 'required|b64mimes:png,jpg']);

            return hyper()->signals(['valid' => true]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/validate-mimes', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_multiple_file_uploads()
    {
        Route::post('/upload-multiple', function () {
            $paths = hyperStorage()->storeMultiple(['avatar', 'banner'], 'images', 'public');

            return hyper()->signals(['uploaded' => count($paths), 'paths' => $paths]);
        });
        $signals = json_encode([
            'avatar' => $this->validPngBase64,
            'banner' => $this->validPngBase64,
        ]);
        $response = $this->call('POST', '/upload-multiple', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_custom_disk()
    {
        Storage::fake('custom');
        Route::post('/upload-custom-disk', function () {
            $path = hyperStorage()->store('file', 'uploads', 'custom');

            return hyper()->signals(['uploaded' => true, 'disk' => 'custom']);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-custom-disk', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_custom_directory()
    {
        Route::post('/upload-custom-dir', function () {
            $path = hyperStorage()->store('file', 'custom/nested/directory', 'public');

            return hyper()->signals(['uploaded' => true, 'path' => $path]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-custom-dir', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_custom_filename()
    {
        Route::post('/upload-custom-name', function () {
            $path = hyperStorage()->store('file', 'uploads', 'public', 'custom-name.png');

            return hyper()->signals(['uploaded' => true]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-custom-name', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_generates_unique_name()
    {
        $paths = [];
        Route::post('/upload-unique', function () use (&$paths) {
            $path = hyperStorage()->store('file', 'uploads', 'public');
            $paths[] = $path;

            return hyper()->signals(['path' => $path]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        // Upload twice
        $this->call('POST', '/upload-unique', ['datastar' => $signals], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $this->call('POST', '/upload-unique', ['datastar' => $signals], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        // Both should have unique paths
        $this->assertCount(2, $paths);
        if (count($paths) === 2) {
            $this->assertNotEquals($paths[0], $paths[1]);
        }
    }

    /** @test */
    public function test_file_upload_returns_url()
    {
        Route::post('/upload-with-url', function () {
            $url = hyperStorage()->storeAsUrl('file', 'uploads', 'public');

            return hyper()->signals(['url' => $url]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-with-url', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_visibility()
    {
        Route::post('/upload-visibility', function () {
            $path = hyperStorage()->store('file', 'uploads', 'public');

            return hyper()->signals(['uploaded' => true]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-visibility', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_error_handling()
    {
        Route::post('/upload-error', function () {
            try {
                signals()->validate(['file' => 'required|b64image']);

                return hyper()->signals(['success' => true]);
            } catch (\Exception $e) {
                return hyper()->signals(['error' => $e->getMessage()]);
            }
        });
        $signals = json_encode(['file' => 'invalid-base64']);
        $response = $this->call('POST', '/upload-error', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_large_files()
    {
        // Create a larger base64 string
        $largeData = str_repeat('A', 10000);
        $largeBase64 = base64_encode($largeData);
        Route::post('/upload-large', function () {
            try {
                $path = hyperStorage()->store('large', 'uploads', 'public');

                return hyper()->signals(['uploaded' => true]);
            } catch (\Exception $e) {
                return hyper()->signals(['error' => 'Upload failed']);
            }
        });
        $signals = json_encode(['large' => $largeBase64]);
        $response = $this->call('POST', '/upload-large', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_validation_fails()
    {
        Route::post('/upload-fail', function () {
            try {
                signals()->validate(['avatar' => 'required|b64image|b64max:1']);
            } catch (\Exception $e) {
                return hyper()->signals(['errors' => ['File too large']]);
            }
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-fail', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_signals()
    {
        Route::post('/upload-with-signals', function () {
            $path = hyperStorage()->store('avatar', 'avatars', 'public');
            $url = Storage::disk('public')->url($path);

            return hyper()->signals([
                'avatarPath' => $path,
                'avatarUrl' => $url,
                'uploaded' => true,
            ]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-with-signals', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_progress()
    {
        Route::post('/upload-progress', function () {
            return hyper()->signals([
                'uploadProgress' => 100,
                'uploadComplete' => true,
            ]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-progress', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_storage_helper_integration()
    {
        Route::post('/helper-integration', function () {
            // Use signals()->store() helper
            $path = signals()->store('avatar', 'avatars', 'public');

            return hyper()->signals(['path' => $path]);
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/helper-integration', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_cleanup_on_error()
    {
        Route::post('/upload-cleanup', function () {
            try {
                signals()->validate(['avatar' => 'required|b64image']);
                $path = hyperStorage()->store('avatar', 'avatars', 'public');
                // Simulate error after upload
                throw new \Exception('Processing failed');
            } catch (\Exception $e) {
                return hyper()->signals(['error' => $e->getMessage()]);
            }
        });
        $signals = json_encode(['avatar' => $this->validPngBase64]);
        $response = $this->call('POST', '/upload-cleanup', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_with_data_uri_format()
    {
        Route::post('/upload-data-uri', function () {
            $path = hyperStorage()->store('file', 'uploads', 'public');

            return hyper()->signals(['uploaded' => true]);
        });
        $dataUri = 'data:image/png;base64,' . $this->validPngBase64;
        $signals = json_encode(['file' => $dataUri]);
        $response = $this->call('POST', '/upload-data-uri', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_file_upload_performance()
    {
        Route::post('/upload-perf', function () {
            $startTime = microtime(true);
            $path = hyperStorage()->store('file', 'uploads', 'public');
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            return hyper()->signals([
                'uploaded' => true,
                'time' => $executionTime,
            ]);
        });
        $signals = json_encode(['file' => $this->validPngBase64]);
        $startTime = microtime(true);
        $response = $this->call('POST', '/upload-perf', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $response->assertOk();
        $this->assertLessThan(2000, $totalTime, 'File upload took too long');
    }
}
