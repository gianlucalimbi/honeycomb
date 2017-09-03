<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Camel Case Keys
    |--------------------------------------------------------------------------
    |
    | When this flag is set to true, Honeycomb will convert all keys in the
    | JSON response to be camelCase, otherwise the case of keys will be
    | unchanged. Honeycomb custom keys are in snake_case.
    |
    */

    'camel_case' => false,

    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    |
    | By default, Honeycomb will use descriptive messages in the response
    | using the Feedback class. By setting this flag to false, only the
    | internal message will be used instead.
    |
    */

    'use_feedback' => true,

    /*
    |--------------------------------------------------------------------------
    | Per Page
    |--------------------------------------------------------------------------
    |
    | Change the minimum and maximum allowed "per page" used in lists.
    | If the values are incompatible, 'per_page_min' will be used.
    |
    */

    'per_page_min' => 10,
    'per_page_max' => 100,

    /*
    |--------------------------------------------------------------------------
    | Per Page Default
    |--------------------------------------------------------------------------
    |
    | Change the default "per page" used in lists,
    | if not supplied as a query argument.
    |
    */

    'per_page_default' => 10,

    /*
    |--------------------------------------------------------------------------
    | Api Exception Wrapper Class
    |--------------------------------------------------------------------------
    |
    | Allows a custom implementation of the ApiExceptionWrapper contract.
    | Specify here the fully qualified class name.
    | Use null for the default implementation.
    |
    */

    'api_exception_wrapper_class' => null,

];
