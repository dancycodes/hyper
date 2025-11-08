<?php

namespace Dancycodes\Hyper\Html\Concerns\Core;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Trait for evaluating closures with dependency injection
 *
 * Based on FilamentPHP's closure evaluation pattern, this trait enables
 * methods to accept closures that are evaluated with automatic dependency
 * injection, supporting both named and typed parameter resolution.
 *
 * @see https://github.com/filamentphp/support
 */
trait EvaluatesClosures
{
    /**
     * Identifier for this element when injected as a closure parameter
     *
     * Example: If set to 'element', closures can receive $element parameter
     */
    protected ?string $evaluationIdentifier = null;

    /**
     * Evaluate a value, executing it if it's a closure with dependency injection
     *
     * Resolution priority:
     * 1. Named injections (by parameter name)
     * 2. Typed injections (by type hint)
     * 3. Default named resolution (via resolveDefaultClosureDependencyForEvaluationByName)
     * 4. Default typed resolution (via resolveDefaultClosureDependencyForEvaluationByType)
     * 5. Self-reference (if parameter name matches $evaluationIdentifier or type matches class)
     * 6. Laravel container resolution
     * 7. Parameter default value
     * 8. Null for optional parameters
     * 9. Exception if unresolvable
     *
     * @param mixed $value The value to evaluate (if Closure, will be executed with DI)
     * @param array<string, mixed> $namedInjections Named parameters to inject (e.g., ['record' => $model])
     * @param array<class-string, mixed> $typedInjections Typed parameters to inject (e.g., [User::class => $user])
     *
     * @return mixed The evaluated value
     */
    public function evaluate(
        mixed $value,
        array $namedInjections = [],
        array $typedInjections = []
    ): mixed {
        // 1. If $value is not a Closure, return it as-is
        if (!$value instanceof Closure) {
            return $value;
        }

        // 2. Create ReflectionFunction from the closure
        $reflection = new ReflectionFunction($value);

        // 3. Get all parameters from the reflection
        $parameters = $reflection->getParameters();

        // 4. For each parameter, resolve its value using resolveClosureDependencyForEvaluation()
        $resolvedParameters = [];
        foreach ($parameters as $parameter) {
            $resolvedParameters[] = $this->resolveClosureDependencyForEvaluation(
                $parameter,
                $namedInjections,
                $typedInjections
            );
        }

        // 5. Call the closure with resolved parameters
        $result = $value(...$resolvedParameters);

        // 6. Recursively evaluate if result is also a closure (nested closures)
        if ($result instanceof Closure) {
            return $this->evaluate($result, $namedInjections, $typedInjections);
        }

        // 7. Return the result
        return $result;
    }

    /**
     * Resolve a single closure parameter using priority-based resolution
     *
     * @param ReflectionParameter $parameter The parameter to resolve
     * @param array<string, mixed> $namedInjections Named parameters provided by caller
     * @param array<class-string, mixed> $typedInjections Typed parameters provided by caller
     *
     * @throws \Exception If parameter cannot be resolved
     *
     * @return mixed The resolved value for this parameter
     */
    protected function resolveClosureDependencyForEvaluation(
        ReflectionParameter $parameter,
        array $namedInjections,
        array $typedInjections
    ): mixed {
        // 1. CHECK NAMED INJECTIONS
        if (array_key_exists($parameter->getName(), $namedInjections)) {
            return $namedInjections[$parameter->getName()];
        }

        // 2. CHECK TYPED INJECTIONS (if parameter has type hint)
        $typeName = $this->getTypedReflectionParameterClassName($parameter);
        if ($typeName && array_key_exists($typeName, $typedInjections)) {
            return $typedInjections[$typeName];
        }

        // 3. CHECK DEFAULT NAMED RESOLUTION (element-specific context)
        $defaultNamed = $this->resolveDefaultClosureDependencyForEvaluationByName($parameter->getName());
        if (!empty($defaultNamed)) {
            return $defaultNamed[0];
        }

        // 4. CHECK DEFAULT TYPED RESOLUTION (element-specific context)
        if ($typeName) {
            $defaultTyped = $this->resolveDefaultClosureDependencyForEvaluationByType($typeName);
            if (!empty($defaultTyped)) {
                return $defaultTyped[0];
            }
        }

        // 5. CHECK SELF-REFERENCE
        // - If parameter name matches $this->evaluationIdentifier, return $this
        if ($this->evaluationIdentifier && $parameter->getName() === $this->evaluationIdentifier) {
            return $this;
        }

        // - If parameter type matches static::class or parent classes, return $this
        if ($typeName && is_a($this, $typeName)) {
            return $this;
        }

        // 6. RESOLVE FROM LARAVEL CONTAINER (if has type hint)
        if ($typeName && app()->bound($typeName)) {
            return app()->make($typeName);
        }

        // 7. USE DEFAULT VALUE (if parameter has default)
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // 8. RETURN NULL (if parameter is explicitly optional/nullable)
        // Note: Only return null if parameter explicitly allows null OR is optional with default
        // Don't return null for untyped parameters without defaults
        if ($parameter->isOptional() || ($parameter->hasType() && $parameter->allowsNull())) {
            return null;
        }

        // 9. THROW EXCEPTION (unresolvable required parameter)
        throw new \Exception(
            "Cannot resolve parameter [{$parameter->getName()}] in closure. " .
            'Parameter is required but no value could be resolved.'
        );
    }

    /**
     * Resolve a closure dependency by parameter name
     *
     * Override this method in subclasses to provide element-specific named context.
     * Return an array with the value as the first element, or empty array if not found.
     *
     * Example in Element class:
     * ```php
     * protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
     * {
     *     return match($parameterName) {
     *         'element' => [$this],
     *         'tag' => [$this->tag],
     *         'attributes' => [$this->attributes],
     *         default => [],
     *     };
     * }
     * ```
     *
     * Example in ContainerElement:
     * ```php
     * protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
     * {
     *     return match($parameterName) {
     *         'container' => [$this],
     *         'children' => [$this->children],
     *         default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
     *     };
     * }
     * ```
     *
     * @param string $parameterName The name of the closure parameter
     *
     * @return array{0: mixed}|array Empty array if not found, or array with value at index 0
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        // Base implementation - no default named parameters
        // Subclasses override to provide context like $element, $record, $children, etc.
        return [];
    }

    /**
     * Resolve a closure dependency by parameter type
     *
     * Override this method in subclasses to provide element-specific typed context.
     * Return an array with the value as the first element, or empty array if not found.
     *
     * Example:
     * ```php
     * protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
     * {
     *     return match($parameterType) {
     *         Element::class => [$this],
     *         ContainerElement::class => [$this],
     *         default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
     *     };
     * }
     * ```
     *
     * @param string $parameterType The fully qualified class name of the parameter type
     *
     * @return array{0: mixed}|array Empty array if not found, or array with value at index 0
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        // Base implementation - no default typed parameters
        // Subclasses override to provide typed context
        return [];
    }

    /**
     * Get the class name from a typed reflection parameter
     *
     * Handles both ReflectionNamedType and ReflectionUnionType.
     * For union types, returns the first class type found.
     *
     * @param ReflectionParameter $parameter The reflection parameter
     *
     * @return string|null The fully qualified class name, or null if not a class type
     */
    protected function getTypedReflectionParameterClassName(ReflectionParameter $parameter): ?string
    {
        // 1. Get the parameter type
        $type = $parameter->getType();

        // 2. If no type, return null
        if (!$type) {
            return null;
        }

        // 3. If ReflectionNamedType and not built-in (e.g., not 'string', 'int', 'bool')
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        // 4. If ReflectionUnionType, find first class type
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof ReflectionNamedType && !$unionType->isBuiltin()) {
                    return $unionType->getName();
                }
            }
        }

        // 5. Return null if no class type found
        return null;
    }
}
