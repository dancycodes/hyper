<?php

namespace Dancycodes\Hyper\Validation;

use Illuminate\Contracts\Validation\Validator;

/**
 * Base64 File Validation Rules
 *
 * Provides Laravel validation rules for base64-encoded file data transmitted through
 * reactive signals. Implements validation methods for image type verification, file size
 * constraints, image dimension requirements, and MIME type checking.
 *
 * Handles both Datastar RC6 format (objects with {name, contents, mime}) and legacy
 * RC5 format (plain base64 strings). File inputs are transmitted as single-element arrays,
 * automatically extracting contents from RC6 objects or using plain strings from legacy
 * format, and stripping data URL prefixes before validation. All validators delegate empty
 * value handling to required rule for consistent behavior.
 *
 * RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}]
 * Legacy Format: ['base64...'] or 'data:image/png;base64,base64...'
 *
 * Validation rules perform memory-based operations without temporary files, using PHP's
 * getimagesizefromstring for image validation and finfo extension for MIME type detection.
 * File sizes are calculated in kilobytes with two decimal precision.
 *
 * Available validation rules: b64image, b64file, b64dimensions, b64max, b64min, b64size,
 * b64mimes. All rules accept empty values as valid, requiring explicit required rule
 * when field presence mandatory.
 *
 * @see \Dancycodes\Hyper\Services\HyperFileStorage
 * @see \Dancycodes\Hyper\HyperServiceProvider::registerValidationRules()
 */
class HyperBase64Validator
{
    /**
     * Validate base64 data represents valid image file
     *
     * Extracts base64 value from signal format, skips validation for empty values,
     * validates base64 format and decoding, then verifies decoded binary data represents
     * valid image using getimagesizefromstring. Accepts any image format PHP can process.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Validation rule parameters (unused)
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if valid image or empty, false otherwise
     */
    public function validateB64image(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        // Security: Check memory safety before decoding
        if (!$this->isSafeToDecodeBase64($value)) {
            return false;
        }

        return $this->isValidBase64Image($value);
    }

    /**
     * Validate base64 data represents valid file with proper encoding
     *
     * Extracts base64 value from signal format, skips validation for empty values,
     * validates base64 format using regex pattern matching and decode verification.
     * Accepts any file type with valid base64 encoding.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Validation rule parameters (unused)
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if valid base64 or empty, false otherwise
     */
    public function validateB64file(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        // Security: Check memory safety before decoding
        if (!$this->isSafeToDecodeBase64($value)) {
            return false;
        }

        return $this->isValidBase64($value);
    }

    /**
     * Validate base64 image dimensions against constraint parameters
     *
     * Extracts base64 value, validates image format, retrieves image dimensions using
     * getimagesizefromstring, and validates dimensions against parameter constraints.
     * Supports min_width, max_width, min_height, max_height, width, height, and ratio
     * constraints using Laravel dimensions rule syntax.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Dimension constraints (e.g., min_width=100)
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if dimensions valid or empty, false otherwise
     */
    public function validateB64dimensions(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        if (!$this->isValidBase64Image($value)) {
            return false;
        }

        $imageInfo = $this->getImageInfoFromBase64($value);
        if ($imageInfo === false) {
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        return $this->validateDimensionParameters($width, $height, $parameters);
    }

    /**
     * Validate base64 file size does not exceed maximum kilobyte threshold
     *
     * Extracts base64 value, validates encoding format, calculates decoded file size in
     * kilobytes with two decimal precision, and compares against maximum threshold from
     * first parameter. File size calculated from decoded binary data length.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Maximum size in kilobytes as first element
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if size within limit or empty, false otherwise
     */
    public function validateB64max(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        if (!$this->isValidBase64($value)) {
            return false;
        }

        $maxKb = (int) $parameters[0];

        // Security: Check memory safety AND max size before decoding
        if (!$this->isSafeToDecodeBase64($value, $maxKb)) {
            return false;
        }

        $sizeKb = $this->getFileSizeFromBase64($value);

        return $sizeKb <= $maxKb;
    }

    /**
     * Validate base64 file size meets minimum kilobyte threshold
     *
     * Extracts base64 value, validates encoding format, calculates decoded file size in
     * kilobytes with two decimal precision, and compares against minimum threshold from
     * first parameter. File size calculated from decoded binary data length.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Minimum size in kilobytes as first element
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if size meets threshold or empty, false otherwise
     */
    public function validateB64min(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        if (!$this->isValidBase64($value)) {
            return false;
        }

        $sizeKb = $this->getFileSizeFromBase64($value);
        $minKb = (int) $parameters[0];

        return $sizeKb >= $minKb;
    }

    /**
     * Validate base64 file size matches exact kilobyte value with tolerance
     *
     * Extracts base64 value, validates encoding format, calculates decoded file size in
     * kilobytes, and compares against expected size from first parameter using floating
     * point comparison with 0.01 tolerance. Accounts for rounding precision in size
     * calculation.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Expected size in kilobytes as first element
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if size matches expected or empty, false otherwise
     */
    public function validateB64size(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        if (!$this->isValidBase64($value)) {
            return false;
        }

        $sizeKb = $this->getFileSizeFromBase64($value);
        $expectedKb = (float) $parameters[0];

        return abs($sizeKb - $expectedKb) < 0.01;
    }

    /**
     * Validate base64 file MIME type matches allowed extension list
     *
     * Extracts base64 value, validates encoding format, detects MIME type from decoded
     * binary data, converts MIME type to file extension, and checks extension exists
     * in allowed parameter list. Uses getimagesizefromstring for images and finfo for
     * other file types.
     *
     * @param string $attribute Validation attribute name
     * @param mixed $value Base64 string or Datastar array format
     * @param array<int, string> $parameters Allowed file extensions (jpg, png, pdf, etc.)
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance
     *
     * @return bool True if MIME type allowed or empty, false otherwise
     */
    public function validateB64mimes(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $value = $this->extractBase64Value($value);

        if (empty($value)) {
            return true;
        }

        if (!$this->isValidBase64($value)) {
            return false;
        }

        $mimeType = $this->getMimeTypeFromBase64($value);
        if (!$mimeType) {
            return false;
        }

        $extension = $this->getExtensionFromMimeType($mimeType);

        return in_array($extension, $parameters);
    }

    /**
     * Extract base64 value from Datastar signal format or plain string
     *
     * Supports both Datastar RC6 format (array of objects with {name, contents, mime})
     * and legacy RC5 format (array of plain base64 strings). Extracts first element if
     * array provided, handles RC6 object structure by accessing 'contents' property,
     * strips data URL metadata prefix if present for backward compatibility, and returns
     * trimmed base64 string.
     *
     * RC6 Format: [{name: 'photo.jpg', contents: 'base64...', mime: 'image/jpeg'}]
     * Legacy Format: ['base64...'] or 'data:image/png;base64,base64...'
     *
     * @param mixed $value Base64 string or Datastar array format (RC5/RC6)
     *
     * @return string Clean base64 string without data URL prefix
     */
    private function extractBase64Value(mixed $value): string
    {
        if (is_array($value)) {
            if (empty($value)) {
                return '';
            }

            $firstItem = $value[0];

            // RC6 format: object with 'contents' key
            if (is_array($firstItem) && isset($firstItem['contents'])) {
                $value = $firstItem['contents'];
            } else {
                // Legacy RC5 format: plain base64 string
                $value = $firstItem;
            }
        }

        if (!is_string($value)) {
            return '';
        }

        // Strip data URI prefix if present (legacy format support)
        if (strpos($value, ';base64,') !== false) {
            [, $value] = explode(',', $value, 2);
        }

        return trim($value);
    }

    /**
     * Validate string represents valid base64 encoding
     *
     * Checks for empty string, validates base64 character set using regex pattern,
     * attempts strict base64 decode, and verifies round-trip encode-decode consistency
     * to ensure data integrity.
     *
     * @param string $value String to validate as base64
     *
     * @return bool True if valid base64 format
     */
    private function isValidBase64(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $value)) {
            return false;
        }

        $decoded = base64_decode($value, true);

        return $decoded !== false && base64_encode($decoded) === $value;
    }

    /**
     * Validate base64 string represents valid image file
     *
     * Validates base64 encoding format, decodes to binary data, and verifies binary
     * data represents valid image using getimagesizefromstring. Performs memory-based
     * validation without temporary files.
     *
     * @param string $base64 Base64-encoded string to validate
     *
     * @return bool True if valid image data
     */
    private function isValidBase64Image(string $base64): bool
    {
        if (!$this->isValidBase64($base64)) {
            return false;
        }

        $binaryData = base64_decode($base64);

        $imageInfo = @getimagesizefromstring($binaryData);

        return $imageInfo !== false;
    }

    /**
     * Retrieve image metadata from base64-encoded data
     *
     * Decodes base64 string to binary data and extracts image dimensions, type, HTML
     * attribute string, and MIME type using getimagesizefromstring. Returns false if
     * binary data does not represent valid image.
     *
     * @param string $base64 Base64-encoded image data
     *
     * @return array{0: int, 1: int, 2: int, 3: string, mime: string}|false Image metadata array or false
     */
    private function getImageInfoFromBase64(string $base64): array|false
    {
        $binaryData = base64_decode($base64);

        return @getimagesizefromstring($binaryData);
    }

    /**
     * Calculate file size in kilobytes from base64-encoded data
     *
     * Decodes base64 string to binary data, calculates byte length of decoded data,
     * converts bytes to kilobytes by dividing by 1024, and rounds result to two
     * decimal places for consistent size comparison.
     *
     * @param string $base64 Base64-encoded file data
     *
     * @return float File size in kilobytes with two decimal precision
     */
    private function getFileSizeFromBase64(string $base64): float
    {
        $binaryData = base64_decode($base64);
        $sizeBytes = strlen($binaryData);

        return round($sizeBytes / 1024, 2);
    }

    /**
     * Detect MIME type from base64-encoded file data
     *
     * Decodes base64 to binary data, attempts MIME type detection using getimagesizefromstring
     * for image files first, then falls back to finfo extension for other file types. Returns
     * null if MIME type cannot be determined.
     *
     * @param string $base64 Base64-encoded file data
     *
     * @return string|null MIME type string or null if detection fails
     */
    private function getMimeTypeFromBase64(string $base64): ?string
    {
        $binaryData = base64_decode($base64);

        $imageInfo = @getimagesizefromstring($binaryData);
        if ($imageInfo !== false) {
            return $imageInfo['mime'];
        }

        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_buffer($finfo, $binaryData);
                finfo_close($finfo);

                return $mimeType ?: null;
            }
        }

        return null;
    }

    /**
     * Convert MIME type to file extension using predefined mapping
     *
     * Maps common MIME types to standard file extensions for image formats, documents,
     * and data files. Returns 'unknown' for MIME types not present in mapping.
     *
     * @param string $mimeType MIME type string to convert
     *
     * @return string File extension without leading dot, 'unknown' if MIME type not mapped
     */
    private function getExtensionFromMimeType(string $mimeType): string
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

        return $mimeMap[$mimeType] ?? 'unknown';
    }

    /**
     * Validate image dimensions against constraint parameters
     *
     * Parses dimension constraint parameters into key-value pairs, iterates through
     * constraints comparing width and height values against min_width, max_width, min_height,
     * max_height, width, height, and ratio requirements. Returns false on first constraint
     * violation, true if all constraints satisfied.
     *
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     * @param array<int, string> $parameters Dimension constraint strings
     *
     * @return bool True if all dimension constraints satisfied
     */
    private function validateDimensionParameters(int $width, int $height, array $parameters): bool
    {
        if (empty($parameters)) {
            return true;
        }

        $constraints = $this->parseDimensionParameters($parameters);

        foreach ($constraints as $constraint => $value) {
            switch ($constraint) {
                case 'min_width':
                    if ($width < $value) {
                        return false;
                    }
                    break;
                case 'max_width':
                    if ($width > $value) {
                        return false;
                    }
                    break;
                case 'min_height':
                    if ($height < $value) {
                        return false;
                    }
                    break;
                case 'max_height':
                    if ($height > $value) {
                        return false;
                    }
                    break;
                case 'width':
                    if ($width !== (int) $value) {
                        return false;
                    }
                    break;
                case 'height':
                    if ($height !== (int) $value) {
                        return false;
                    }
                    break;
                case 'ratio':
                    $ratio = $width / $height;
                    if (abs($ratio - (float) $value) > 0.01) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Parse dimension constraint parameters into key-value pairs
     *
     * Parses Laravel dimensions rule syntax extracting constraint key-value pairs from
     * parameter strings. Handles ratio constraints with fraction format, converting
     * numerator/denominator to float ratio. Converts other constraint values to integers.
     *
     * @param array<int, string> $parameters Dimension constraint strings (e.g., min_width=100)
     *
     * @return array<string, float|int> Parsed constraint name to value mapping
     */
    private function parseDimensionParameters(array $parameters): array
    {
        $constraints = [];

        foreach ($parameters as $parameter) {
            if (strpos($parameter, '=') !== false) {
                [$key, $value] = explode('=', $parameter, 2);

                if ($key === 'ratio') {
                    if (strpos($value, '/') !== false) {
                        [$numerator, $denominator] = explode('/', $value, 2);
                        $constraints[$key] = (float) $numerator / (float) $denominator;
                    } else {
                        $constraints[$key] = (float) $value;
                    }
                } else {
                    $constraints[$key] = (int) $value;
                }
            }
        }

        return $constraints;
    }

    /**
     * Validate base64 string size is safe for memory decoding
     *
     * Estimates decoded binary size from base64 string length and compares against
     * available PHP memory to prevent memory exhaustion attacks. Base64 encoding
     * increases size by approximately 33%, so binary size ≈ (base64 length * 3) / 4.
     *
     * Checks against both PHP memory_limit and maximum allowed file size to ensure
     * safe decoding operation. Returns false if estimated size exceeds 50% of available
     * memory or if memory limit cannot be determined.
     *
     * @param string $base64 Base64-encoded string to validate
     * @param int|null $maxSizeKb Maximum allowed file size in KB (null for memory-only check)
     *
     * @return bool True if safe to decode, false if would exceed memory limits
     */
    private function isSafeToDecodeBase64(string $base64, ?int $maxSizeKb = null): bool
    {
        // Estimate binary size from base64 length
        // Base64 is ~33% larger: binary ≈ (base64 * 3) / 4
        $estimatedBytes = (strlen($base64) * 3) / 4;
        $estimatedKb = $estimatedBytes / 1024;

        // Check against maximum file size if provided
        if ($maxSizeKb !== null && $estimatedKb > $maxSizeKb) {
            return false;
        }

        // Get PHP memory limit
        $memoryLimit = ini_get('memory_limit');

        // If unlimited (-1), allow any size
        if ($memoryLimit === '-1') {
            return true;
        }

        // Convert memory limit to bytes
        $memoryBytes = $this->convertMemoryLimitToBytes($memoryLimit);

        // Require file to be less than 50% of available memory for safety
        // This leaves room for other operations and prevents OOM
        $maxSafeBytes = $memoryBytes * 0.5;

        return $estimatedBytes <= $maxSafeBytes;
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
