<?php

namespace Dancycodes\Hyper\Http;

use Dancycodes\Hyper\Exceptions\HyperSignalTamperedException;
use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

/**
 * Hyper Signal Manager - Server-Side Signal State Management
 *
 * Manages reactive signal state synchronized between server and client, providing methods
 * for reading, validating, storing, and securing signal data transmitted via Datastar protocol.
 * Implements locked signal security mechanism to prevent client-side tampering of sensitive data.
 *
 * Signal data is transmitted from the client either via GET parameter (datastar key) or request
 * body as JSON-encoded data. The manager parses this data and provides convenient access methods
 * following Laravel conventions (get, has, collect, only, validate).
 *
 * Locked signals are identified by trailing underscore in their names (e.g., userId_) and are
 * validated against server-side session storage to detect tampering. Server-stored values are
 * encrypted with Laravel's Crypt facade using MAC for integrity verification.
 *
 * The class distinguishes between first calls (initial page load or no existing locked signals)
 * and subsequent calls (Hyper requests with existing locked signals) to properly handle signal
 * initialization and updates.
 *
 * File storage integration provides methods for handling base64-encoded file uploads from
 * client-side signals, with automatic decoding and storage to Laravel's filesystem.
 *
 * @see \Dancycodes\Hyper\Services\HyperFileStorage
 * @see \Dancycodes\Hyper\Exceptions\HyperSignalTamperedException
 * @see \Dancycodes\Hyper\Exceptions\HyperValidationException
 */
class HyperSignal
{
    protected Request $request;

    /** @var array<string, mixed>|null Cached parsed signal data from request */
    protected ?array $signals = null;

    /** @var string Query parameter key for reading signals from GET requests */
    protected string $signalKey = 'datastar';

    /** @var string Session key for storing encrypted locked signal data */
    protected string $lockedSignalsKey = 'hyper_locked_signals';

    /** @var bool Whether this is the first call in request lifecycle */
    protected bool $isFirstCall = false;

    /**
     * Lazy-loaded file storage service for base64 file handling
     */
    private ?\Dancycodes\Hyper\Services\HyperFileStorage $fileStorage = null;

    /**
     * Initialize signal manager with request and detect first-call status
     *
     * @param \Illuminate\Http\Request $request Current HTTP request instance
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->detectFirstCall();
    }

    /**
     * Determine if this is the first call in the request lifecycle
     *
     * First call is identified when locked signals session data does not exist or when
     * the request is not a Hyper request (indicating initial page load). This distinction
     * affects locked signal storage behavior: first calls clear existing locked signals
     * before storing new ones, while subsequent calls merge with existing data.
     */
    protected function detectFirstCall(): void
    {
        $this->isFirstCall = !session()->has($this->lockedSignalsKey) ||
            /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
            !$this->request->isHyper();
    }

    /**
     * Retrieve all signals from request with automatic locked signal validation
     *
     * Lazy-loads and caches signal data from request on first access. Automatically validates
     * locked signals against server-side session storage if any locked signals (names ending
     * with underscore) are present in the parsed data. Throws exception if tampering detected.
     *
     *
     *
     *
     * @throws \Dancycodes\Hyper\Exceptions\HyperSignalTamperedException When locked signals validation fails
     *
     * @return array<string, mixed> Associative array of signal names and values
     */
    public function all(): array
    {
        if ($this->signals === null) {
            $this->signals = $this->readSignals();

            if ($this->hasLockedSignals($this->signals)) {
                $this->validateLockedSignals();
            }
        }

        return $this->signals ?? [];
    }

    /**
     * Retrieve specific signal value with optional default fallback
     *
     * Accesses the full signal array (triggering locked signal validation if applicable)
     * and extracts the requested value using Laravel's data_get helper, which supports
     * dot notation for nested array access.
     *
     * @param string $key Signal name, supports dot notation for nested values
     * @param mixed $default Default value returned if signal does not exist
     *
     * @throws \Dancycodes\Hyper\Exceptions\HyperSignalTamperedException When locked signals validation fails
     *
     * @return mixed Signal value or default if not found
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $signals = $this->all();

        return data_get($signals, $key, $default);
    }

    /**
     * Determine if signal array contains any locked signals
     *
     * Scans signal keys for trailing underscore character which identifies locked signals
     * requiring server-side validation. Used to conditionally trigger validation logic.
     *
     * @param array<string, mixed> $signals Signal data to check
     *
     * @return bool True if any signal name ends with underscore, false otherwise
     */
    protected function hasLockedSignals(array $signals): bool
    {
        foreach (array_keys($signals) as $signalName) {
            if (str_ends_with((string) $signalName, '_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store locked signals in encrypted session storage for tamper validation
     *
     * Extracts locked signals from provided data and stores them securely in session using
     * Laravel's Crypt facade with MAC for integrity verification. Behavior differs based on
     * first-call detection: first calls clear existing locked signals before storing, while
     * subsequent calls merge new locked signals with existing ones.
     *
     * Stored data includes signals array, timestamp, and session ID to enable comprehensive
     * validation against tampering. Returns early if no locked signals present or JSON encoding fails.
     *
     * @param array<string, mixed> $signals Complete signal data including locked signals
     */
    public function storeLockedSignals(array $signals): void
    {
        $lockedSignals = $this->extractLockedSignals($signals);

        if (empty($lockedSignals)) {
            return;
        }

        if ($this->isFirstCall) {
            $this->clearLockedSignals();
            $finalLockedSignals = $lockedSignals;
        } else {
            $existingLocked = $this->getStoredLockedSignals() ?? [];
            $finalLockedSignals = array_merge($existingLocked, $lockedSignals);
        }

        $jsonEncoded = json_encode([
            'signals' => $finalLockedSignals,
            'timestamp' => now()->timestamp,
            'session_id' => session()->getId(),
        ]);

        if ($jsonEncoded === false) {
            return;
        }

        $signedLockedSignals = Crypt::encryptString($jsonEncoded);

        session()->put($this->lockedSignalsKey, $signedLockedSignals);
    }

    /**
     * Retrieve locked signals from encrypted session storage
     *
     * Decrypts and extracts locked signal data from session. Returns null if no locked signals
     * exist, decryption fails, or data structure is invalid. Silently handles DecryptException
     * by returning null rather than exposing encryption errors.
     *
     * @return array<string, mixed>|null Locked signals array or null if unavailable/invalid
     */
    public function getStoredLockedSignals(): ?array
    {
        if (!session()->has($this->lockedSignalsKey)) {
            return null;
        }

        try {
            $encryptedData = session()->get($this->lockedSignalsKey);

            if (!is_string($encryptedData)) {
                return null;
            }

            $decryptedString = Crypt::decryptString($encryptedData);
            $decryptedData = json_decode($decryptedString, true);

            if (!is_array($decryptedData)) {
                return null;
            }

            /** @var array<string, mixed>|null $signals */
            $signals = $decryptedData['signals'] ?? null;

            return is_array($signals) ? $signals : null;
        } catch (DecryptException $e) {
            return null; // Invalid signature, treat as no locked signals
        }
    }

    /**
     * Remove all locked signals from session storage
     *
     * Clears the encrypted locked signals data from session, typically called during first-call
     * initialization to reset locked signal state before storing new values.
     */
    public function clearLockedSignals(): void
    {
        session()->forget($this->lockedSignalsKey);
    }

    /**
     * Remove specific locked signal from session storage
     *
     * Deletes individual locked signal from encrypted session storage. Returns early if signal
     * name does not end with underscore (not a locked signal) or if signal not found in storage.
     * Clears entire locked signals session data if removing the last locked signal.
     *
     * @param string $signalName Name of locked signal to remove (must end with underscore)
     */
    public function clearLockedSignal(string $signalName): void
    {
        if (!str_ends_with($signalName, '_')) {
            return;
        }

        $existingLocked = $this->getStoredLockedSignals();
        if ($existingLocked && isset($existingLocked[$signalName])) {
            unset($existingLocked[$signalName]);

            if (empty($existingLocked)) {
                $this->clearLockedSignals();
            } else {
                $this->storeLockedSignalsDirectly($existingLocked);
            }
        }
    }

    /**
     * Update specific locked signal value in session storage
     *
     * Modifies value of individual locked signal in encrypted session storage. Supports Datastar's
     * null-value deletion protocol where null value triggers signal removal. Returns early if signal
     * name does not end with underscore (not a locked signal).
     *
     * @param string $signalName Name of locked signal to update (must end with underscore)
     * @param mixed $value New signal value, or null to delete the signal
     */
    public function updateLockedSignal(string $signalName, mixed $value): void
    {
        if (!str_ends_with($signalName, '_')) {
            return;
        }

        if ($value === null) {
            $this->clearLockedSignal($signalName);

            return;
        }

        $existingLocked = $this->getStoredLockedSignals() ?? [];
        $existingLocked[$signalName] = $value;
        $this->storeLockedSignalsDirectly($existingLocked);
    }

    /**
     * Delete signal following Datastar null-value protocol
     *
     * Removes locked signal from server-side session storage if name ends with underscore.
     * Non-locked signals are not handled server-side as their deletion occurs automatically
     * in the client-side signal store via Datastar protocol.
     *
     * @param string $signalName Signal name to delete
     */
    public function deleteSignal(string $signalName): void
    {
        if (str_ends_with($signalName, '_')) {
            $this->clearLockedSignal($signalName);
        }
    }

    /**
     * Store locked signals bypassing first-call detection logic
     *
     * Encrypts and stores locked signals directly to session without checking first-call status
     * or merging with existing data. Used internally for atomic signal updates and deletions.
     *
     * @param array<string, mixed> $lockedSignals Locked signal data to store
     */
    protected function storeLockedSignalsDirectly(array $lockedSignals): void
    {
        $jsonEncoded = json_encode([
            'signals' => $lockedSignals,
            'timestamp' => now()->timestamp,
            'session_id' => session()->getId(),
        ]);

        if ($jsonEncoded === false) {
            return; // JSON encoding failed
        }

        $signedLockedSignals = Crypt::encryptString($jsonEncoded);

        session()->put($this->lockedSignalsKey, $signedLockedSignals);
    }

    /**
     * Validate locked signals against encrypted session storage for tampering detection
     *
     * Compares current locked signals from request against server-stored values to detect
     * client-side tampering. Throws exception if stored values differ from current values,
     * if unexpected locked signals appear, or if decryption fails. Allows signal removal
     * (absence in current data) as valid operation per Datastar null-deletion protocol.
     *
     * @throws \Dancycodes\Hyper\Exceptions\HyperSignalTamperedException When tampering detected or decryption fails
     */
    protected function validateLockedSignals(): void
    {
        if (!session()->has($this->lockedSignalsKey)) {
            return; // No locked signals to validate
        }

        try {
            // Decrypt and verify signature
            $encryptedData = session()->get($this->lockedSignalsKey);

            if (!is_string($encryptedData)) {
                return;
            }

            $decryptedString = Crypt::decryptString($encryptedData);
            $decryptedData = json_decode($decryptedString, true);

            if (!is_array($decryptedData) || !isset($decryptedData['signals'])) {
                return;
            }

            $storedLockedSignals = $decryptedData['signals'];

            if (!is_array($storedLockedSignals)) {
                return;
            }

            $currentLockedSignals = $this->extractLockedSignals($this->signals ?? []);

            // Compare stored vs current locked signals
            foreach ($storedLockedSignals as $signalName => $originalValue) {
                if (!is_string($signalName)) {
                    continue;
                }

                if (!isset($currentLockedSignals[$signalName])) {
                    // Point 4: Allow null deletion - signal was removed
                    continue;
                }

                if ($currentLockedSignals[$signalName] !== $originalValue) {
                    throw new HyperSignalTamperedException(
                        "Locked signal '{$signalName}' was tampered with."
                    );
                }
            }

            // Check for new locked signals that weren't stored
            foreach ($currentLockedSignals as $signalName => $currentValue) {
                if (!isset($storedLockedSignals[$signalName])) {
                    throw new HyperSignalTamperedException(
                        "Unexpected locked signal '{$signalName}' was added."
                    );
                }
            }

        } catch (DecryptException $e) {
            throw new HyperSignalTamperedException(
                'Locked signals signature is invalid. Possible tampering detected.'
            );
        }
    }

    /**
     * Extract signals ending with '_' (locked signals)
     *
     * @param array<string, mixed> $signals
     *
     * @return array<string, mixed>
     */
    protected function extractLockedSignals(array $signals): array
    {
        return array_filter($signals, function ($value, $key) {
            /** @phpstan-ignore function.alreadyNarrowedType */
            return is_string($key) && str_ends_with($key, '_');
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Read signals from request (existing Datastar-compatible method)
     *
     * @return array<string, mixed>
     */
    private function readSignals(): array
    {
        // Method 1: Check GET parameters first (exactly like Datastar)
        $input = $this->request->get($this->signalKey);

        // Method 2: If no GET parameter, read request body (exactly like Datastar)
        if ($input === null) {
            $content = $this->request->getContent();
            $input = !empty($content) ? $content : null;
        }

        // Parse the input exactly like Datastar does
        if ($input === null) {
            return [];
        }

        // Handle case where input is already an array (e.g., from tests using request()->merge())
        if (is_array($input)) {
            /** @var array<string, mixed> $input */
            return $input;
        }

        if (!is_string($input)) {
            return [];
        }

        $signals = json_decode($input, true);

        return is_array($signals) ? $signals : [];
    }

    // ... (keep all existing methods: has, collect, only, validate, store, storeAsUrl, storeMultiple, etc.)
    // ... (no changes needed to existing methods)

    /**
     * Check if a signal exists
     */
    public function has(string $key): bool
    {
        $signals = $this->all();

        return data_get($signals, $key) !== null;
    }

    /**
     * Get signals as a Laravel Collection
     *
     * @return Collection<string, mixed>
     */
    public function collect(): Collection
    {
        return collect($this->all());
    }

    /**
     * Get only specific signals
     *
     * @param array<int, string> $keys
     *
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $signals = $this->all();

        return array_intersect_key($signals, array_flip($keys));
    }

    /**
     * Validate signals using Laravel's validation system
     *
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     *
     * @return array<string, mixed>
     */
    public function validate(array $rules, array $messages = [], array $attributes = []): array
    {
        $data = $this->all();

        /** @var array<string, array<int, string>> $errors */
        $errors = signals('errors') ?? [];

        if (count($errors) > 0) {
            foreach ($rules as $key => $value) {
                if (isset($errors[$key])) {
                    $errors[$key] = [];
                }
            }
        }

        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new HyperValidationException($validator, $errors);
        }

        hyper()->signals(['errors' => $errors]);

        return $validator->validated();
    }

    /**
     * Get the HyperFileStorage instance (lazy-loaded)
     */
    private function getFileStorage(): \Dancycodes\Hyper\Services\HyperFileStorage
    {
        if ($this->fileStorage === null) {
            $this->fileStorage = app(\Dancycodes\Hyper\Services\HyperFileStorage::class);
        }

        return $this->fileStorage;
    }

    /**
     * Store a base64 file from signals to Laravel storage
     */
    public function store(string $signalKey, string $directory = '', string $disk = 'public', ?string $filename = null): string
    {
        return $this->getFileStorage()->store($signalKey, $directory, $disk, $filename);
    }

    /**
     * Store base64 data and return public URL
     */
    public function storeAsUrl(string $signalKey, string $directory = '', string $disk = 'public', ?string $filename = null): string
    {
        return $this->getFileStorage()->storeAsUrl($signalKey, $directory, $disk, $filename);
    }

    /**
     * Store multiple base64 files at once
     *
     * @param array<string, string> $mapping
     *
     * @return array<string, string>
     */
    public function storeMultiple(array $mapping, string $disk = 'public'): array
    {
        return $this->getFileStorage()->storeMultiple($mapping, $disk);
    }

    /**
     * Static helper for quick access
     *
     * @return array<string, mixed>
     */
    public static function read(): array
    {
        return app(self::class)->all();
    }
}
