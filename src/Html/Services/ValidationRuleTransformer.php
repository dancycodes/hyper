<?php

namespace Dancycodes\Hyper\Html\Services;

/**
 * Transforms Laravel validation rules to HTML5 validation attributes
 *
 * Provides progressive enhancement by converting server-side validation
 * rules into client-side HTML5 attributes for immediate feedback.
 *
 * @example
 * ValidationRuleTransformer::toHtml5Attributes('required|email|max:255')
 * // Returns: ['required' => '', 'type' => 'email', 'maxlength' => '255']
 */
class ValidationRuleTransformer
{
    /**
     * Transform Laravel validation rules to HTML5 attributes
     *
     * @param  string  $rules  Pipe-separated validation rules
     * @return array<string, string> HTML5 attributes
     */
    public static function toHtml5Attributes(string $rules): array
    {
        $attributes = [];

        foreach (explode('|', $rules) as $rule) {
            $html5 = self::transformRule(trim($rule));
            if ($html5) {
                $attributes = array_merge($attributes, $html5);
            }
        }

        return $attributes;
    }

    /**
     * Transform individual validation rule
     *
     * @param  string  $rule  Single validation rule
     * @return array<string, string>|null HTML5 attributes or null
     */
    protected static function transformRule(string $rule): ?array
    {
        return match (true) {
            $rule === 'required' => ['required' => ''],
            $rule === 'email' => ['type' => 'email'],
            $rule === 'url' => ['type' => 'url'],
            $rule === 'numeric' || $rule === 'integer' => ['type' => 'number'],

            str_starts_with($rule, 'min:') => self::handleMin($rule),
            str_starts_with($rule, 'max:') => self::handleMax($rule),
            str_starts_with($rule, 'between:') => self::handleBetween($rule),
            str_starts_with($rule, 'regex:') => self::handleRegex($rule),

            default => null
        };
    }

    /**
     * Handle min:value rule
     *
     * @param  string  $rule  min:value rule
     * @return array<string, string>
     */
    protected static function handleMin(string $rule): array
    {
        $value = substr($rule, 4);

        // For text inputs: minlength, for numbers: min
        return ['minlength' => $value, 'min' => $value];
    }

    /**
     * Handle max:value rule
     *
     * @param  string  $rule  max:value rule
     * @return array<string, string>
     */
    protected static function handleMax(string $rule): array
    {
        $value = substr($rule, 4);

        return ['maxlength' => $value, 'max' => $value];
    }

    /**
     * Handle between:min,max rule
     *
     * @param  string  $rule  between:min,max rule
     * @return array<string, string>
     */
    protected static function handleBetween(string $rule): array
    {
        [$min, $max] = explode(',', substr($rule, 8));

        return [
            'minlength' => trim($min),
            'maxlength' => trim($max),
            'min' => trim($min),
            'max' => trim($max),
        ];
    }

    /**
     * Handle regex:pattern rule
     *
     * @param  string  $rule  regex:pattern rule
     * @return array<string, string>
     */
    protected static function handleRegex(string $rule): array
    {
        $pattern = trim(substr($rule, 6), '/');

        return ['pattern' => $pattern];
    }
}
