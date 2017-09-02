<?php

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
