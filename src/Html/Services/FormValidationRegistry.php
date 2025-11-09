<?php

namespace Dancycodes\Hyper\Html\Services;

/**
 * Registry for live validation rules
 *
 * Stores validation rules for fields with live: true enabled,
 * allowing auto-route to validate individual fields in real-time.
 *
 * This service is registered as a singleton in the Laravel container
 * and used by the live validation route (/validate/{field}).
 */
class FormValidationRegistry
{
    /**
     * Validation rules indexed by field name
     *
     * @var array<string, string>
     */
    protected array $rules = [];

    /**
     * Custom validation messages indexed by field name
     *
     * @var array<string, array<string, string>>
     */
    protected array $messages = [];

    /**
     * Custom attribute names indexed by field name
     *
     * @var array<string, array<string, string>>
     */
    protected array $attributes = [];

    /**
     * Register validation rules for a field
     *
     * @param  string  $field  Field name
     * @param  string  $rules  Validation rules
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, string>  $attributes  Custom attribute names
     */
    public function register(string $field, string $rules, array $messages = [], array $attributes = []): void
    {
        $this->rules[$field] = $rules;

        if (! empty($messages)) {
            $this->messages[$field] = $messages;
        }

        if (! empty($attributes)) {
            $this->attributes[$field] = $attributes;
        }
    }

    /**
     * Get validation rules for a specific field
     *
     * @param  string  $field  Field name
     * @return string|null Validation rules or null if not found
     */
    public function getRulesForField(string $field): ?string
    {
        return $this->rules[$field] ?? null;
    }

    /**
     * Get custom error messages for a specific field
     *
     * @param  string  $field  Field name
     * @return array<string, string> Custom error messages
     */
    public function getMessagesForField(string $field): array
    {
        return $this->messages[$field] ?? [];
    }

    /**
     * Get custom attribute names for a specific field
     *
     * @param  string  $field  Field name
     * @return array<string, string> Custom attribute names
     */
    public function getAttributesForField(string $field): array
    {
        return $this->attributes[$field] ?? [];
    }

    /**
     * Clear all registered validation data
     */
    public function clear(): void
    {
        $this->rules = [];
        $this->messages = [];
        $this->attributes = [];
    }
}
