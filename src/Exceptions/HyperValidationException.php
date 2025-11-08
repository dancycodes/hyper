<?php

namespace Dancycodes\Hyper\Exceptions;

use Dancycodes\Hyper\Http\HyperResponse;
use Illuminate\Validation\ValidationException;

/**
 * Reactive Validation Exception
 *
 * Exception thrown during reactive signal validation failures, extending Laravel's
 * ValidationException with reactive response rendering. Stores validation error messages
 * extracted from validator instance and automatically renders reactive responses containing
 * errors signal for client-side error display.
 *
 * Integrates with data-error attribute for automatic error message display in Blade templates.
 * Client receives validation errors through signals update, enabling reactive form validation
 * feedback without full page reload. Error messages maintain Laravel validation error bag
 * structure with field names as keys and error message arrays as values.
 *
 * Simplifies validation error handling in reactive controllers by providing single exception
 * type for signal validation failures. Controllers throw exception with validator instance,
 * exception automatically extracts errors and builds reactive response.
 *
 * @see \Dancycodes\Hyper\Http\HyperSignal::validate()
 */
class HyperValidationException extends ValidationException
{
    /**
     * Validation error messages indexed by field name
     *
     * @var array<string, array<int, string>>
     */
    protected array $errors;

    /**
     * Initialize validation exception from validator instance
     *
     * Extracts validation error messages from validator error bag and stores in exception
     * property for reactive response rendering. Delegates to parent ValidationException
     * constructor for standard Laravel validation exception initialization.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator Validator instance with errors
     * @param \Symfony\Component\HttpFoundation\Response|null $response Optional custom response
     * @param string $errorBag Error bag name for multiple validation contexts
     */
    public function __construct($validator, $errors, $response = null, $errorBag = 'default')
    {
        parent::__construct($validator, $response, $errorBag);
        $errors = [...$errors, ...$validator->errors()->toArray()];
        $this->errors = $errors;
    }

    /**
     * Render exception as reactive response with validation errors
     *
     * Generates reactive response containing errors signal for client-side error display.
     * Errors signal updates automatically populate data-error attributes in Blade templates,
     * providing reactive validation feedback without JavaScript error handling.
     *
     * @param \Illuminate\Http\Request $request Current HTTP request
     *
     * @return \Dancycodes\Hyper\Http\HyperResponse Reactive response with errors signal
     */
    public function render(\Illuminate\Http\Request $request): HyperResponse
    {
        return hyper()->signals([
            'errors' => $this->errors,
        ]);
    }

    /**
     * Retrieve validation error messages indexed by field name
     *
     * Returns error message array with field names as keys and error message arrays as
     * values, matching Laravel validation error bag structure.
     *
     * @return array<string, array<int, string>> Field name to error message array mapping
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
