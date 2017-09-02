<?php

use Honeycomb\ApiException;
use Honeycomb\Feedback;

if (!function_exists('abort_api')) {
    /**
     * Throw an ApiException with the given data.
     *
     * @param int $status
     * @param Feedback|string $error
     * @param array|object $errorDescription
     *
     * @return void
     *
     * @throws ApiException
     */
    function abort_api($status, $error, $errorDescription = null)
    {
        if (!($error instanceof Feedback)) {
            $error = Feedback::error($error, trans('honeycomb::errors.generic'));
        }

        throw new ApiException($status, $error, $errorDescription, null);
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
        return is_array($var) && (array_merge($var) !== $var || !is_numeric(implode(array_keys($var))));
    }
}
