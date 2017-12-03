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
abort_api(412, 'validation failed');
```

This will create a JSON that looks like this:

```json
{
    "status": 412,
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

throw new ApiException(412, $error);
// or
return response()->apiFailure(new ApiException(412, $error));
// or
return ApiResponse::failure(new ApiException(412, $error));
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

...

## i18n

...

## Automatic Exception Wrapping

...

## Roadmap

...
