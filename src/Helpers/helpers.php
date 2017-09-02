<?php

use Honeycomb\ApiException;
use Honeycomb\Feedback;
use Illuminate\Contracts\Support\Arrayable;

if (!function_exists('abort_api')) {
    /**
     * Throw an ApiException with the given data.
     *
     * @param int $status
     * @param Feedback|string $error
     * @param array|object $errors
     * @param Throwable|Exception|null $previous
     *
     * @throws ApiException
     */
    function abort_api($status, $error, $errors = null, $previous = null)
    {
        if (!($error instanceof Feedback)) {
            $error = Feedback::error($error, trans('honeycomb::errors.generic'));
        }

        throw new ApiException($status, $error, $errors, $previous);
    }
}

if (!function_exists('transform_array_keys')) {
    /**
     * Transform array keys using given transform Closure.
     *
     * @param array|ArrayAccess|Arrayable $array
     * @param Closure|string $transform
     *
     * @return array
     */
    function transform_array_keys($array, $transform)
    {
        $result = [];

        if ($array instanceof Arrayable) {
            $array = $array->toArray();
        }

        foreach ($array as $key => $value) {
            $transformedKey = $key;
            if (is_string($key)) {
                $transformedKey = $transform($key);
            }

            $result[$transformedKey] = is_arrayable($value) ? transform_array_keys($value, $transform) : $value;
        }

        return $result;
    }
}

if (!function_exists('is_arrayable')) {
    /**
     * Checks whether $var is something like an array.
     * E.g. array, ArrayAccess or Arrayable
     *
     * @param $var
     *
     * @return boolean
     */
    function is_arrayable($var)
    {
        return is_array($var) || $var instanceof ArrayAccess || $var instanceof Arrayable;
    }
}

if (!function_exists('is_sequential_array')) {
    /**
     * Checks whether $var is a sequential array.
     * E.g. [ <value>, <value>, <value>, ... ]
     *
     * @param $var
     *
     * @return boolean
     */
    function is_sequential_array($var)
    {
        // empty arrays can be both sequential and associative
        if (is_array($var) && empty($var)) {
            return true;
        }

        return is_array($var) && array_merge($var) === $var && is_numeric(implode(array_keys($var)));
    }
}

if (!function_exists('is_associative_array')) {
    /**
     * Checks whether $var is an associative array.
     * E.g. [ <key> => <value>, <key> => <value>, <key> => <value>, ... ]
     *
     * @param $var
     *
     * @return boolean
     */
    function is_associative_array($var)
    {
        // empty arrays can be both sequential and associative
        if (is_array($var) && empty($var)) {
            return true;
        }

        return is_array($var) && (array_merge($var) !== $var || !is_numeric(implode(array_keys($var))));
    }
}
