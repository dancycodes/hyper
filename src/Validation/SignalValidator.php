<?php

namespace Dancycodes\Hyper\Validation;

use Dancycodes\Hyper\Exceptions\HyperValidationException;
use Dancycodes\Hyper\Http\HyperSignal;
use Illuminate\Support\Facades\Validator;

/**
 * Signal-Centric Validation System
 *
 * Validates SIGNALS (reactive state), not form fields. This is a fundamental shift
 * from traditional form validation to reactive validation that aligns with Hyper's
 * signal-driven architecture.
 *
 * Key Concepts:
 * - Validates signal paths (e.g., 'email', 'user.email', 'userId_')
 * - Supports nested signals via dot notation ('user.profile.bio')
 * - Enforces security for locked signals (ends with '_')
 * - Rejects local signals (starts with '_') from server validation
 * - Integrates with Laravel's validation system
 *
 * Signal Path Inference:
 * 1. Priority 1: Uses data-bind attribute value ('user.email')
 * 2. Priority 2: Falls back to name attribute ('email')
 *
 * Signal Types:
 * - Regular: 'email', 'name', 'user.email' → Normal validation
 * - Locked: 'userId_', 'sessionId_' → Tamper-proof, encrypted in session
 * - Local: '_tempEdit', '_mode' → Client-only, CANNOT be validated server-side
 *
 * Security Features:
 * - Local signals throw exception (prevent server validation of client-only state)
 * - Locked signals validated with integrity checks
 * - Nested signals extracted safely via data_get()
 *
 * Usage Example:
 * ```php
 * $validator = app(SignalValidator::class);
 *
 * // Register signal validation
 * $validator->register('user.email', 'required|email');
 * $validator->register('userId_', 'required|integer'); // Locked signal
 *
 * // Validate against current signals
 * try {
 *     $validator->validate(signals());
 * } catch (HyperValidationException $e) {
 *     // Exception auto-renders error response
 * }
 * ```
 *
 * @see \Dancycodes\Hyper\Html\Concerns\Attributes\Form\HasValidation
 * @see \Dancycodes\Hyper\Exceptions\HyperValidationException
 * @see https://laravel-hyper.com/docs/validation
 */
class SignalValidator
{
    /**
     * Session key for storing validation rules
     */
    protected string $sessionKey = 'hyper_signal_validation_rules';

    /**
     * Validation rules indexed by signal path
     *
     * Examples:
     * - 'email' => 'required|email'
     * - 'user.email' => 'required|email|max:255'
     * - 'userId_' => 'required|integer'
     *
     * @var array<string, string>
     */
    protected array $rules = [];

    /**
     * Custom validation messages indexed by signal path
     *
     * @var array<string, array<string, string>>
     */
    protected array $messages = [];

    /**
     * Custom attribute names indexed by signal path
     *
     * @var array<string, array<string, string>>
     */
    protected array $attributes = [];

    /**
     * Whether this is the first call in request lifecycle
     */
    protected bool $isFirstCall = false;

    /**
     * Initialize validator with lifecycle detection
     *
     * Detects if this is a fresh page load (first call) or subsequent AJAX request.
     * On first call, clears all previous rules. On subsequent calls, loads from session.
     */
    public function __construct()
    {
        $this->detectFirstCall();

        if ($this->isFirstCall) {
            $this->clearAll();
        } else {
            $this->loadFromSession();
        }
    }

    /**
     * Determine if this is the first call in the request lifecycle
     *
     * First call is identified when validation rules session data does not exist or when
     * the request is not a Hyper request (indicating initial page load). This distinction
     * affects rule storage behavior: first calls clear existing rules before storing new ones,
     * while subsequent calls merge with existing data.
     */
    protected function detectFirstCall(): void
    {
        $this->isFirstCall = !session()->has($this->sessionKey) ||
            !request()->hasHeader('Datastar-Request');
    }

    /**
     * Register validation rules for a signal path
     *
     * Stores rules in both memory and session for persistence across requests.
     * This enables live validation to work on subsequent AJAX requests.
     *
     * @param string $signalPath Signal path (e.g., 'email', 'user.email', 'userId_')
     * @param string $rules Laravel validation rules
     * @param array<string, string> $messages Custom error messages
     * @param array<string, string> $attributes Custom attribute names
     *
     * @throws \RuntimeException If attempting to register local signal (starts with '_')
     */
    public function register(string $signalPath, string $rules, array $messages = [], array $attributes = []): void
    {
        // Security: Reject local signals (client-only state)
        if ($this->isLocalSignal($signalPath)) {
            throw new \RuntimeException(
                "Cannot register validation for local signal '{$signalPath}'. " .
                'Local signals (prefixed with _) are client-only and cannot be validated server-side. ' .
                'Use clientSide: true for HTML5 validation only.'
            );
        }

        $this->rules[$signalPath] = $rules;

        if (!empty($messages)) {
            $this->messages[$signalPath] = $messages;
        }

        if (!empty($attributes)) {
            $this->attributes[$signalPath] = $attributes;
        }

        // Store in session for persistence across requests (live validation)
        $this->storeInSession();
    }

    /**
     * Validate all registered signals against current signal state
     *
     * @param HyperSignal $signals Current signal state from request
     *
     * @throws HyperValidationException If validation fails
     *
     * @return array<string, mixed> Validated data
     */
    public function validate(HyperSignal $signals): array
    {
        $data = [];
        $validationRules = [];
        $validationMessages = [];
        $validationAttributes = [];

        // Extract signal values and build validation arrays
        foreach ($this->rules as $signalPath => $rules) {
            // Extract value from signal using path
            $value = $this->getSignalValue($signals, $signalPath);

            // Build nested data structure for Laravel validator
            // Laravel validator expects nested arrays for dot notation
            // E.g., 'user.email' => ['user' => ['email' => value]]
            data_set($data, $signalPath, $value);

            $validationRules[$signalPath] = $rules;

            // Add custom messages if defined
            if (isset($this->messages[$signalPath])) {
                foreach ($this->messages[$signalPath] as $rule => $message) {
                    $validationMessages["{$signalPath}.{$rule}"] = $message;
                }
            }

            // Add custom attributes if defined
            if (isset($this->attributes[$signalPath])) {
                $validationAttributes = array_merge($validationAttributes, $this->attributes[$signalPath]);
            }
        }

        // Validate using Laravel validator
        /** @var array<string, mixed> $data */
        $validator = Validator::make($data, $validationRules, $validationMessages, $validationAttributes);

        if ($validator->fails()) {
            throw new HyperValidationException($validator);
        }

        // Return validated data with signal paths as keys (flat structure)
        $validated = [];
        foreach ($this->rules as $signalPath => $rules) {
            $validated[$signalPath] = data_get($data, $signalPath);
        }

        return $validated;
    }

    /**
     * Validate a single signal path against current signal state
     *
     * Used by live validation route to validate individual signals as user types.
     *
     * @param string $signalPath Signal path to validate
     * @param HyperSignal $signals Current signal state
     *
     * @throws HyperValidationException If validation fails
     * @throws \RuntimeException If signal path not registered
     *
     * @return mixed Validated signal value
     */
    public function validateSingle(string $signalPath, HyperSignal $signals): mixed
    {
        // Check if signal path is registered
        if (!isset($this->rules[$signalPath])) {
            throw new \RuntimeException(
                "Signal path '{$signalPath}' is not registered for validation. " .
                "Add validate(live: true) to the element with data-bind=\"{$signalPath}\"."
            );
        }

        // Extract signal value
        $value = $this->getSignalValue($signals, $signalPath);

        // Build nested data structure for Laravel validator
        $data = [];
        data_set($data, $signalPath, $value);

        $rules = [$signalPath => $this->rules[$signalPath]];
        $messages = [];
        $attributes = [];

        // Add custom messages
        if (isset($this->messages[$signalPath])) {
            foreach ($this->messages[$signalPath] as $rule => $message) {
                $messages["{$signalPath}.{$rule}"] = $message;
            }
        }

        // Add custom attributes
        if (isset($this->attributes[$signalPath])) {
            $attributes = $this->attributes[$signalPath];
        }

        // Validate
        /** @var array<string, mixed> $data */
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new HyperValidationException($validator);
        }

        return $value;
    }

    /**
     * Validate all signals that have registered rules
     *
     * Only validates signals that are present in the incoming signal data
     * and have rules registered. Useful for automatic validation.
     *
     * @param HyperSignal $signals Current signal state
     *
     * @throws HyperValidationException If validation fails
     *
     * @return array<string, mixed> Validated data
     */
    public function validateAllRegistered(HyperSignal $signals): array
    {
        if (empty($this->rules)) {
            return [];
        }

        $data = [];
        $validationRules = [];
        $validationMessages = [];
        $validationAttributes = [];

        // Only validate signals that have rules AND are present in request
        foreach ($this->rules as $signalPath => $rules) {
            $value = $this->getSignalValue($signals, $signalPath);

            // Skip if signal not present (null and not explicitly set)
            if ($value === null && !$signals->has($signalPath)) {
                continue;
            }

            // Build nested data structure for Laravel validator
            data_set($data, $signalPath, $value);
            $validationRules[$signalPath] = $rules;

            // Add custom messages
            if (isset($this->messages[$signalPath])) {
                foreach ($this->messages[$signalPath] as $rule => $message) {
                    $validationMessages["{$signalPath}.{$rule}"] = $message;
                }
            }

            // Add custom attributes
            if (isset($this->attributes[$signalPath])) {
                $validationAttributes = array_merge($validationAttributes, $this->attributes[$signalPath]);
            }
        }

        if (empty($validationRules)) {
            return [];
        }

        // Validate
        /** @var array<string, mixed> $data */
        $validator = Validator::make($data, $validationRules, $validationMessages, $validationAttributes);

        if ($validator->fails()) {
            throw new HyperValidationException($validator);
        }

        // Return validated data with signal paths as keys
        $validated = [];
        foreach ($validationRules as $signalPath => $rules) {
            $validated[$signalPath] = data_get($data, $signalPath);
        }

        return $validated;
    }

    /**
     * Validate specific signals using their registered rules
     *
     * @param array<int, string> $signalPaths Signal paths to validate
     * @param HyperSignal $signals Current signal state
     *
     * @throws HyperValidationException If validation fails
     * @throws \RuntimeException If signal path not registered
     *
     * @return array<string, mixed> Validated data
     */
    public function validateRegistered(array $signalPaths, HyperSignal $signals): array
    {
        $data = [];
        $validationRules = [];
        $validationMessages = [];
        $validationAttributes = [];

        foreach ($signalPaths as $signalPath) {
            // Check if rules exist
            if (!isset($this->rules[$signalPath])) {
                throw new \RuntimeException(
                    "Signal path '{$signalPath}' is not registered for validation. " .
                    'Add validation rules via ->validate() or ->signalValidation().'
                );
            }

            $value = $this->getSignalValue($signals, $signalPath);

            // Build nested data structure
            data_set($data, $signalPath, $value);
            $validationRules[$signalPath] = $this->rules[$signalPath];

            // Add custom messages
            if (isset($this->messages[$signalPath])) {
                foreach ($this->messages[$signalPath] as $rule => $message) {
                    $validationMessages["{$signalPath}.{$rule}"] = $message;
                }
            }

            // Add custom attributes
            if (isset($this->attributes[$signalPath])) {
                $validationAttributes = array_merge($validationAttributes, $this->attributes[$signalPath]);
            }
        }

        // Validate
        /** @var array<string, mixed> $data */
        $validator = Validator::make($data, $validationRules, $validationMessages, $validationAttributes);

        if ($validator->fails()) {
            throw new HyperValidationException($validator);
        }

        // Return validated data with signal paths as keys
        $validated = [];
        foreach ($validationRules as $signalPath => $rules) {
            $validated[$signalPath] = data_get($data, $signalPath);
        }

        return $validated;
    }

    /**
     * Check if validation rules exist for a signal path
     *
     * @param string $signalPath Signal path to check
     *
     * @return bool True if rules exist
     */
    public function hasRulesFor(string $signalPath): bool
    {
        return isset($this->rules[$signalPath]);
    }

    /**
     * Check if any validation rules are registered
     *
     * @return bool True if any rules exist
     */
    public function hasRegisteredRules(): bool
    {
        return !empty($this->rules);
    }

    /**
     * Unregister validation rules for a signal path
     *
     * Used when explicit rules override registered rules.
     *
     * @param string $signalPath Signal path to unregister
     */
    public function unregister(string $signalPath): void
    {
        unset($this->rules[$signalPath]);
        unset($this->messages[$signalPath]);
        unset($this->attributes[$signalPath]);

        // Update session
        $this->storeInSession();
    }

    /**
     * Get validation rules for a specific signal path
     *
     * @param string $signalPath Signal path
     *
     * @return string|null Validation rules or null if not registered
     */
    public function getRulesForSignal(string $signalPath): ?string
    {
        return $this->rules[$signalPath] ?? null;
    }

    /**
     * Get custom error messages for a specific signal path
     *
     * @param string $signalPath Signal path
     *
     * @return array<string, string> Custom error messages
     */
    public function getMessagesForSignal(string $signalPath): array
    {
        return $this->messages[$signalPath] ?? [];
    }

    /**
     * Get custom attribute names for a specific signal path
     *
     * @param string $signalPath Signal path
     *
     * @return array<string, string> Custom attribute names
     */
    public function getAttributesForSignal(string $signalPath): array
    {
        return $this->attributes[$signalPath] ?? [];
    }

    /**
     * Clear all registered validation data
     *
     * Clears both memory and session storage.
     * Alias for clearAll() for backward compatibility.
     */
    public function clear(): void
    {
        $this->clearAll();
    }

    /**
     * Clear all validation rules from memory and session
     *
     * Called on first request (page load) to ensure clean state.
     */
    protected function clearAll(): void
    {
        $this->rules = [];
        $this->messages = [];
        $this->attributes = [];

        session()->forget($this->sessionKey);
    }

    /**
     * Extract signal value using dot notation path
     *
     * Supports:
     * - Simple paths: 'email' → $signals->get('email')
     * - Nested paths: 'user.email' → data_get($signals->all(), 'user.email')
     * - Locked signals: 'userId_' → $signals->get('userId_')
     *
     * @param HyperSignal $signals Signal state
     * @param string $path Signal path
     *
     * @return mixed Signal value
     */
    protected function getSignalValue(HyperSignal $signals, string $path): mixed
    {
        // Handle nested paths with dot notation
        if (str_contains($path, '.')) {
            return data_get($signals->all(), $path);
        }

        // Simple path
        return $signals->get($path);
    }

    /**
     * Check if signal path represents a locked signal
     *
     * Locked signals end with '_' and are tamper-proof (encrypted in session).
     *
     * @param string $signalPath Signal path
     *
     * @return bool True if locked signal
     */
    protected function isLockedSignal(string $signalPath): bool
    {
        // Get the last segment for nested paths
        $segments = explode('.', $signalPath);
        $lastSegment = end($segments);

        return str_ends_with($lastSegment, '_');
    }

    /**
     * Check if signal path represents a local signal
     *
     * Local signals start with '_' and are client-only (never sent to server).
     *
     * @param string $signalPath Signal path
     *
     * @return bool True if local signal
     */
    protected function isLocalSignal(string $signalPath): bool
    {
        // Get the first segment for nested paths
        $segments = explode('.', $signalPath);
        $firstSegment = $segments[0];

        return str_starts_with($firstSegment, '_');
    }

    /**
     * Store validation rules in session for persistence across requests
     *
     * Enables live validation to work on subsequent AJAX requests.
     */
    protected function storeInSession(): void
    {
        session()->put($this->sessionKey, [
            'rules' => $this->rules,
            'messages' => $this->messages,
            'attributes' => $this->attributes,
        ]);
    }

    /**
     * Load validation rules from session
     *
     * Called in constructor to restore rules from previous requests.
     */
    protected function loadFromSession(): void
    {
        /** @var array{rules?: array<string, string>, messages?: array<string, array<string, string>>, attributes?: array<string, array<string, string>>} $stored */
        $stored = session()->get($this->sessionKey, []);

        $this->rules = $stored['rules'] ?? [];
        $this->messages = $stored['messages'] ?? [];
        $this->attributes = $stored['attributes'] ?? [];
    }
}
