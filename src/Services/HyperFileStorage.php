<?php

namespace Dancycodes\Hyper\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Base64 File Storage Service
 *
 * Handles storage operations for base64-encoded files transmitted through reactive signals.
 * Provides methods for storing base64 data to Laravel filesystem disks with automatic MIME
 * type detection, extension resolution, and unique filename generation.
 *
 * Supports flexible input formats for maximum compatibility:
 * - RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}] (from file inputs)
 * - Raw base64: 'base64string...' or ['base64string...'] (manual/custom formats)
 *
 * RC6 format provides additional metadata (filename, MIME type) accessible via getFilename()
 * and getMimeType() methods. Raw base64 format works seamlessly but without metadata.
 *
 * Files are stored using Laravel Storage facade without creating temporary files on disk,
 * enabling efficient memory-based processing for uploaded content. MIME type detection
 * uses PHP's getimagesizefromstring for images and finfo extension for other file types.
 *
 * @see \Dancycodes\Hyper\Http\HyperSignal
 * @see \Dancycodes\Hyper\Validation\HyperBase64Validator
 */
class HyperFileStorage
{
    /**
     * Store base64-encoded file from signal to Laravel filesystem disk
     *
     * Extracts base64 data from signal using provided key, validates data exists, decodes
     * binary content, detects MIME type and extension, generates unique filename if not
     * provided, and stores file to specified disk and directory path.
     *
     * Automatically uses the original filename from RC6 metadata if no custom filename
     * is provided, preserving the user's original file name. Set $filename to false to
     * force auto-generation.
     *
     * @param string $signalKey Signal key containing base64 file data
     * @param string $directory Target directory path within disk (optional)
     * @param string $disk Laravel filesystem disk name (default: public)
     * @param string|null|false $filename Custom filename with extension (null: use RC6 name, false: force auto-generate)
     *
     * @throws \InvalidArgumentException When signal key contains no base64 data
     *
     * @return string Stored file path relative to disk root
     */
    public function store(string $signalKey, string $directory = '', string $disk = 'public', string|null|false $filename = null): string
    {
        $base64Data = $this->extractBase64FromSignals($signalKey);

        if (empty($base64Data)) {
            throw new InvalidArgumentException("No base64 data found for signal: {$signalKey}");
        }

        // Use RC6 filename if no custom filename provided
        if ($filename === null) {
            $filename = $this->getFilename($signalKey);
        }

        // Convert false to null for auto-generation
        if ($filename === false) {
            $filename = null;
        }

        return $this->storeBase64Data($base64Data, $directory, $disk, $filename);
    }

    /**
     * Store base64-encoded file and return public URL
     *
     * Stores file using store() method then generates public URL for stored file path
     * using Laravel Storage URL generation. Requires disk configuration to support
     * public URL generation.
     *
     * @param string $signalKey Signal key containing base64 file data
     * @param string $directory Target directory path within disk (optional)
     * @param string $disk Laravel filesystem disk name (default: public)
     * @param string|null $filename Custom filename with extension (auto-generated if null)
     *
     * @throws \InvalidArgumentException When signal key contains no base64 data
     *
     * @return string Public URL to stored file
     */
    public function storeAsUrl(string $signalKey, string $directory = '', string $disk = 'public', ?string $filename = null): string
    {
        $path = $this->store($signalKey, $directory, $disk, $filename);

        return Storage::disk($disk)->url($path);
    }

    /**
     * Store multiple base64-encoded files in batch operation
     *
     * Iterates through signal key to directory mapping, checks each signal exists using
     * signals() helper, and stores files that have data present. Skips signals that do
     * not exist without throwing exceptions, allowing partial batch processing.
     *
     * @param array<string, string> $mapping Signal key to directory path mapping
     * @param string $disk Laravel filesystem disk name (default: public)
     *
     * @throws \InvalidArgumentException When any present signal key contains invalid base64 data
     *
     * @return array<string, string> Signal key to stored file path mapping for successfully stored files
     */
    public function storeMultiple(array $mapping, string $disk = 'public'): array
    {
        $results = [];

        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();

        foreach ($mapping as $signalKey => $directory) {
            if ($hyperSignal->has((string) $signalKey)) {
                $results[$signalKey] = $this->store((string) $signalKey, $directory, $disk);
            }
        }

        return $results;
    }

    /**
     * Extract base64 data from signal value with flexible format support
     *
     * Supports both RC6 format and raw base64 strings for maximum flexibility:
     * - RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}]
     * - Raw base64: 'base64string...' or ['base64string...']
     * - Data URI: 'data:image/png;base64,base64string...'
     *
     * @param string $key Signal key containing file data
     *
     * @return string Clean base64 string, empty string if no data
     */
    private function extractBase64FromSignals(string $key): string
    {
        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();
        $value = $hyperSignal->get($key);

        // Handle raw string (base64 passed directly or data URI)
        if (is_string($value)) {
            $value = trim($value);

            // Strip data URI prefix if present (e.g., "data:image/png;base64,")
            if (str_starts_with($value, 'data:')) {
                $parts = explode(',', $value, 2);
                if (count($parts) === 2) {
                    $value = $parts[1];
                }
            }

            return $value;
        }

        // Handle array format
        if (is_array($value)) {
            if (empty($value)) {
                return '';
            }

            $firstItem = $value[0];

            // RC6 format: object with 'contents' key
            if (is_array($firstItem) && isset($firstItem['contents'])) {
                $contents = $firstItem['contents'];
                if (is_string($contents)) {
                    return trim($contents);
                }
            }

            // Raw format: plain base64 string in array
            if (is_string($firstItem)) {
                return trim($firstItem);
            }
        }

        return '';
    }

    /**
     * Extract original filename from Datastar RC6 signal format
     *
     * Retrieves signal value and extracts the original filename from RC6 metadata.
     * Returns null when filename is not available. Useful for preserving original
     * filenames during storage operations.
     *
     * RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}]
     *
     * @param string $signalKey Signal key containing file data
     *
     * @return string|null Original filename if available, null otherwise
     */
    public function getFilename(string $signalKey): ?string
    {
        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();
        $value = $hyperSignal->get($signalKey);

        if (!is_array($value) || empty($value)) {
            return null;
        }

        $firstItem = $value[0];

        if (is_array($firstItem) && isset($firstItem['name']) && is_string($firstItem['name'])) {
            return $firstItem['name'];
        }

        return null;
    }

    /**
     * Extract MIME type from Datastar RC6 signal format
     *
     * Retrieves signal value and extracts the MIME type from RC6 metadata.
     * Returns null when MIME type is not available. Useful for validation and
     * content-type determination without decoding base64 data.
     *
     * RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}]
     *
     * @param string $signalKey Signal key containing file data
     *
     * @return string|null MIME type if available, null otherwise
     */
    public function getMimeType(string $signalKey): ?string
    {
        /** @var \Dancycodes\Hyper\Http\HyperSignal $hyperSignal */
        $hyperSignal = signals();
        $value = $hyperSignal->get($signalKey);

        if (!is_array($value) || empty($value)) {
            return null;
        }

        $firstItem = $value[0];

        if (is_array($firstItem) && isset($firstItem['mime']) && is_string($firstItem['mime'])) {
            return $firstItem['mime'];
        }

        return null;
    }

    /**
     * Decode and store base64 data to Laravel filesystem disk
     *
     * Decodes base64 string to binary content, detects file extension from binary data
     * MIME type if filename not provided, generates unique filename with extension,
     * constructs full path with directory prefix, and stores binary data using Laravel
     * Storage facade without creating temporary files.
     *
     * @param string $base64Data Base64-encoded file content
     * @param string $directory Target directory path within disk
     * @param string $disk Laravel filesystem disk name
     * @param string|null $filename Custom filename with extension or null for auto-generation
     *
     * @return string Stored file path relative to disk root
     */
    private function storeBase64Data(string $base64Data, string $directory, string $disk, ?string $filename): string
    {
        // Security: Validate base64 size is safe to decode into memory
        $estimatedBytes = (strlen($base64Data) * 3) / 4;
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit !== '-1') {
            $memoryBytes = $this->convertMemoryLimitToBytes($memoryLimit);
            $maxSafeBytes = $memoryBytes * 0.5; // Use max 50% of memory

            if ($estimatedBytes > $maxSafeBytes) {
                throw new \RuntimeException(
                    'File too large: ' . round($estimatedBytes / 1024 / 1024, 2) . 'MB exceeds ' .
                    'safe memory limit of ' . round($maxSafeBytes / 1024 / 1024, 2) . 'MB. ' .
                    'Increase PHP memory_limit or reduce file size.'
                );
            }
        }

        $binaryData = base64_decode($base64Data);

        if (!$filename) {
            $extension = $this->detectExtensionFromBinary($binaryData);
            $filename = $this->generateUniqueFilename($extension);
        }

        $path = $directory ? trim($directory, '/') . '/' . $filename : $filename;

        Storage::disk($disk)->put($path, $binaryData);

        return $path;
    }

    /**
     * Detect file extension from binary data MIME type
     *
     * Attempts MIME type detection using getimagesizefromstring for image files first,
     * then falls back to finfo extension for general file type detection. Converts
     * detected MIME type to file extension using predefined mapping.
     *
     * @param string $binaryData Decoded binary file content
     *
     * @return string File extension without leading dot, 'bin' as fallback
     */
    private function detectExtensionFromBinary(string $binaryData): string
    {
        $imageInfo = @getimagesizefromstring($binaryData);
        if ($imageInfo) {
            return $this->mimeToExtension($imageInfo['mime']);
        }

        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_buffer($finfo, $binaryData);
                finfo_close($finfo);
                if ($mimeType) {
                    return $this->mimeToExtension($mimeType);
                }
            }
        }

        return 'bin';
    }

    /**
     * Convert MIME type to file extension using predefined mapping
     *
     * Maps common MIME types to their standard file extensions for image formats,
     * documents, and data files. Returns 'bin' as fallback extension for unknown
     * or unsupported MIME types.
     *
     * @param string $mimeType MIME type string from file detection
     *
     * @return string File extension without leading dot, 'bin' if MIME type unknown
     */
    private function mimeToExtension(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/json' => 'json',
            'text/csv' => 'csv',
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }

    /**
     * Generate unique filename using timestamp and random component
     *
     * Creates filename using uniqid() with more entropy enabled, prefixed with 'file_'
     * and suffixed with provided extension. Ensures filename uniqueness across concurrent
     * upload operations.
     *
     * @param string $extension File extension without leading dot
     *
     * @return string Unique filename with extension
     */
    private function generateUniqueFilename(string $extension): string
    {
        return uniqid('file_', true) . '.' . $extension;
    }

    /**
     * Convert PHP memory limit string to bytes
     *
     * Parses memory_limit configuration value supporting K, M, G suffixes and
     * converts to bytes for comparison. Returns PHP_INT_MAX for unlimited (-1)
     * or invalid formats.
     *
     * @param string $memoryLimit Memory limit from ini_get (e.g., "128M", "1G")
     *
     * @return int Memory limit in bytes
     */
    private function convertMemoryLimitToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);

        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }
}
