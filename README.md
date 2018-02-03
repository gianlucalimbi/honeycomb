# Honeycomb

Sweet JSON API for Laravel

## Features

Honeycomb lets you easily create JSON APIs, creating a custom JSON Response based on your data. It features automatic pagination for lists, exception handling and expressive feedback.

This simple line of code:

```php
return response()->api(200, 'article', $article);
```

outputs a JSON that looks like this:

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

For the full feature list, visit the [documentation].

## Requirements

Honeycomb requires Laravel 5.1 or higher.

## Installation and Usage

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

In order to take advantage of the automatic exception handling, you have to update the `app/Exceptions/Handler.php` file as described in the [documentation]:

If you want to update the default configurations or language lines, use this artisan command:

```bash
php artisan vendor:publish --provider="Honeycomb\HoneycombServiceProvider" --tag=config
```

You can access the configurations or language lines as follows:

```php
<?php

$config = config('honeycomb.config_name');
$lang = trans('honeycomb::file.line');
```

For the full usage guide, visit the [documentation].

[documentation]: https://gianlucalimbi.github.io/honeycomb/
