Honeycomb lets you easily create JSON APIs, creating a custom JSON Response based on your data. It features automatic pagination for lists, exception handling and expressive feedback.

### Table of Contents
{:.no_toc}

1.  Table of Contents
{:toc}

## Requirements

Honeycomb requires Laravel 5.1 or higher.

## Installation

To install Honeycomb, add it to your composer dependencies:

```bash
composer require gianlucalimbi/honeycomb
```

If you are using Laravel up to version 5.4, add `HoneycombServiceProvider` to your `app.php` config file:

```php
'providers' => [
    // ...
    Honeycomb\HoneycombServiceProvider::class,
],
```

This won't be needed if you are using Laravel 5.5 or higher, as Honeycomb uses Laravel's [Package Auto-Discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518).

## Responses

Honeycomb provides a custom `Response` (`ApiResponse`) and a custom `Exception` (`ApiException`) classes.

### Successful Response

You can create an API successful response using the `api` response macro as follows:

```php
return response()->api(200, 'article', $article);
```

This will create a JSON that looks like this:

```json
{
    "status": 200,
    "article": {
        "id": 42,
        "title": "My Fancy Article",
        "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        "category": {
            "title": "Awesome Articles",
            "description": "Best Articles of the Interweb"
        },
        "tags": [
            {
                "title": "Foo",
                "description": ""
            },
            {
                "title": "Bar",
                "description": ""
            }
        ]
    },
    "feedback": null,
    "metadata": {
        "name": "article"
    }
}
```

The `api` function accepts these arguments:

```php
function api ($status, $name, $data = null, $feedback = null, $metadata = [], $headers = [])
```

1.  `$status`: the HTTP status code. Only `2xx` status codes are allowed here, an `InvalidArgumentException` is thrown otherwise.

2.  `$name`: the name of the object in the JSON response, for example `data` or `list`. Note that [these](https://github.com/gianlucalimbi/honeycomb/blob/master/src/ApiResponse.php#L32) are reserved names that cannot be used.

3.  `$data`: your main response data. Usually an Eloquent model or a Collection, but anything can be passed here. Defaults to `null`.

4.  `$feedback`: a set of expressive feedback that can contain custom messages. This must be an associative array like this:

    ```php
    $feedback = [
        'name' => [ $feedback1, $feedback2, /* ... */ ],
        // ...
    ];
    ```

    You can learn more about Feedback [here](#feedback). Defaults to `null`.

5.  `$metadata`: an associative array containing additional data relative to your content. By default it contains information about the name of your data and about [pagination](#pagination). Defaults to an empty array.

6.  `$headers`: an associative array containing custom HTTP headers that can be set in the response. Defaults to an empty array.

You can also manually create a new successful `ApiResponse` instance by using the `ApiResponse::success()` function that accepts the same arguments as the `api` response macro:

```php
return ApiResponse::success(200, 'article', $article);
```

### Failure Response

You can create an API failure response using the `abort_api` helper function as follows:

```php
abort_api(422, 'validation failed');
```

This will create a JSON that looks like this:

```json
{
    "status": 422,
    "error": {
        "type": "error",
        "message": "validation failed",
        "description": "An error occurred. Please try again."
    },
    "errors": null
}
```

The `abort_api` function accepts these arguments:

```php
function abort_api($status, $error, $errors = null, $previous = null)
```

1.  `$status`: the HTTP status code. Only `4xx` and `5xx` status codes are allowed here, an `InvalidArgumentException` is thrown otherwise.

2.  `$error`: a string or a Feedback that represents the error. You can learn more about feedback [here](#feedback).

3.  `$errors`: additional errors feedback that can contain custom messages. This must be an associative array like this:

    ```php
    $errors = [
        'name' => [ $error1, $error2, /* ... */ ],
        // ...
    ];
    ```

    You can learn more about Feedback [here](#feedback). Defaults to `null`.

4.  `$previous`: an `Exception` that you are wrapping. Defaults to `null`.

You can also manually create an `ApiException` and use it in the a new failure `ApiResponse` instance by using the `response()->apiFailure()` or the `ApiResponse::failure()` functions:

```php
$error = Feedback::error('validation failed', 'An error occurred. Please try again.');

throw new ApiException(422, $error);
// or
return response()->apiFailure(new ApiException(422, $error));
// or
return ApiResponse::failure(new ApiException(422, $error));
```

Note that in the `response()->apiFailure()` and `ApiResponse::failure()` functions you can pass a `$headers` associative array as a second argument, similar to the `response()->api()` function.

## Feedback

The `Feedback` class is used to represent a message in the JSON response. It contains information for both the consumer of the API and the end-user.

The `Feedback` constructor accepts these arguments:

```php
function __construct($type, $message, $description)
```

1.  `$type`: the type of the feedback. It can be one of `success`, `info`, `warning` or `error`.

2.  `$message`: the internal message that describes the feedback.

3.  `$description`: user-friendly description of the feedback. This should be ready to be shown to the user.

You can also use the following shorthand functions to create a `Feedback` with the relate type:

```php
Feedback::success($message, $description);
Feedback::info($message, $description);
Feedback::warning($message, $description);
Feedback::error($message, $description);
```

If you don't want to use expressive feedback and just use the internal message string, you can disable feedback in the [configuration](#configuration) file. In this case:
- every instance of `Feedback` will be replaced with its `$message` property;
- you can pass a string wherever a `Feedback` is expected (e.g. in the `response()->apiFailure()` and `ApiResponse::failure()` functions).

This is a sample failure response with feedback enabled:

```json
{
    "status": 422,
    "error": {
        "type": "error",
        "message": "validation failed",
        "description": "An error occurred. Please try again."
    },
    "errors": {
        "email": [
            {
                "type": "error",
                "message": "email field email rule failed",
                "description": "The email must be a valid email address."
            }
        ],
        "password": [
            {
                "type": "error",
                "message": "password field min:6 rule failed",
                "description": "The password must be at least 6 characters."
            },
            {
                "type": "error",
                "message": "password field alpha rule failed",
                "description": "The password may only contain letters."
            }
        ]
    }
}
```

This is the same response, but with feedback disabled:

```json
{
    "status": 422,
    "error": "validation failed",
    "errors": {
        "email": [
            "email field email rule failed"
        ],
        "password": [
            "password field min:6 rule failed",
            "password field alpha rule failed"
        ]
    }
}
```

## Pagination

When using a list (`Collection`, array, `Eloquent\Builder` or `Query\Builder`) for `$data` in a successful API response, you can enable automatic pagination using the `setPaginated()` function:

```php
return response()->api(200, 'list', $list)->setPaginated();
```

Honeycomb will search for `page` and `per_page` query arguments to determine the correct portion of the list to return.

By default, the `per_page` value can be between 10 and 100. 10 is used if not specified. These values can be changed in the [configuration](#configuration) file.

When using pagination, the following keys will be added in the `metadata` section of the response:
- `count`: the total count of items in the list;
- `page_count`: the total number of pages;
- `page`: the current requested page;
- `per_page`: the number of items show per page.

## Configuration

You can customize some aspects of Honeycomb by updating the configuration file.

Use this artisan command to publish the configuration in your `app` folder:

```bash
php artisan vendor:publish --provider="Honeycomb\HoneycombServiceProvider" --tag=config
```

You can now edit the `app/config/honeycomb.php` file.

Use this to access a configuration:

```php
$config = config('honeycomb.config_name');
```

### Camel Case
{:.no_toc}

When this flag is set to `true`, Honeycomb will convert all keys in the JSON response to be camelCase, otherwise the case of keys will be unchanged. Also the `per_page` query argument used for pagination must be used in camelCase (`perPage`). By default, Honeycomb custom keys are in snake_case.

```php
'camel_case' => false,
```

### Use Feedback
{:.no_toc}

By default, Honeycomb will use descriptive messages in the response using the `Feedback` class. By setting this flag to false, only the internal message will be used instead.

```php
'use_feedback' => true,
```

### Pagination
{:.no_toc}

Change the minimum, the maximum and the default `per_page` values used in lists. If the minimum and maximum values are incompatible, only `per_page_min` will be used.

```php
'per_page_min' => 10,
'per_page_max' => 100,
'per_page_default' => 10,
```

### Api Exception Wrapper Class
{:.no_toc}

Allows a custom implementation of the ApiExceptionWrapper contract. Specify the fully qualified class name. Use `null` for the default implementation.

You can learn more about Automatic Exception Wrapping [here](#automatic-exception-wrapping).

```php
'api_exception_wrapper_class' => \App\Exceptions\CustomApiExceptionWrapper::class,
```

## i18n

The language lines used for `Feedback`'s `$description` when using the [Automatic Exception Wrapping](#automatic-exception-wrapping) are located in a language file. Only a `error.php` file is used right now.

Use this artisan command to publish the translation in your `app` folder:

```bash
php artisan vendor:publish --provider="Honeycomb\HoneycombServiceProvider" --tag=lang
```

You can now edit the `resources/lang/vendor/honeycomb/errors.php` file.

Use this to access a translation:

```php
$lang = trans('honeycomb::file.line');
```

## Automatic Exception Wrapping

Honeycomb can wrap any Exception in an `ApiException` that can be used as a JSON Response. In order to take advantage of this you have to update the `app/Exceptions/Handler.php` file as follows:

1.  Make the class extend `Honeycomb\Exceptions\Handler` instead of `Illuminate\Foundation\Exceptions\Handler`

2.  Implement the `isApi` function and return `true` if the `Request` passed as an arguments should have a JSON Response

3.  Rename the `render` to `renderException`, if you want to add a custom renderer for the Exception

4. Make sure NOT to override the `render` function

For example:

```php
<?php

namespace App\Exceptions;

use Honeycomb\Exceptions\Handler as HoneycombExceptionHandler;

class Handler extends HoneycombExceptionHandler
{

    /**
     * Determines if current request is an API request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return boolean
     */
    public function isApi($request)
    {
        return $request->is('api/*');
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function renderException($request, Exception $exception)
    {
        // just an example for a custom response
        if ($exception instanceof SomeException) {
            return response()->view('errors.some_view');
        }

        // use Laravel default render function
        return parent::renderException($request, $exception);
    }

    // the rest of your handler...

}
```

When the `isApi` function returns `true`, the Exception will be wrapped in an `ApiException` using an instance of `ApiExceptionWrapper` and it will be used to create a failure `ApiResponse`.

When the `isApi` function returns `false`, the `renderException` function will be called and the Exception will be rendered as usual.

### ApiExceptionWrapper
{:.no_toc}

To wrap a normal `Exception` in an `ApiException`, Honeycomb uses a `ApiExceptionWrapper` helper class. In this class are defined a set of rules that explains how Exceptions should be wrapped.

By default Honeycomb can wrap these Exceptions:
- `HttpException`
- `HttpResponseException`
- `ModelNotFoundException`
- `Exception` (as a fallback)

When using Laravel 5.2 or higher, also these Exceptions are supported:
- `AuthenticationException`
- `ValidationException`

You can provide your own implementation extending `Honeycomb\Support\BaseApiExceptionWrapper` and specifying your class in the configuration, as described [here](#configuration).

Define a `$exceptions` property that contains an array of the `Exception`s that you can wrap and the corresponding function that will be called to wrap them.

For example:

```php
protected $exceptions = [
    \Illuminate\Auth\AuthenticationException::class => 'wrapAuthenticationException',
];

protected function wrapAuthenticationException($exception)
{
    $error = Feedback::error('unauthorized', trans('honeycomb::errors.authentication'));

    return new ApiException(401, $error, $errors, $exception);
}
```

## Roadmap

What I'm planning for the next releases:

- Additional `Link` headers in paginated response, like `next`, `prev`, etc

- Automatic embeds/includes support using Eloquent models (e.g. `/api/posts?with=author`)
