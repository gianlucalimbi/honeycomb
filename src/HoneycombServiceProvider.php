<?php

namespace Honeycomb;

use Honeycomb\Contracts\ApiExceptionWrapper as ApiExceptionWrapperContract;
use Honeycomb\Support\ApiExceptionWrapper;
use Illuminate\Support\ServiceProvider;

/**
 * Class HoneycombServiceProvider.
 *
 * The glue that keeps Honeycomb all together.
 *
 * @package Honeycomb
 */
class HoneycombServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // publish config file
        $this->publishes([
            __DIR__ . '/config/honeycomb.php' => config_path('honeycomb.php'),
        ], 'config');

        // load translations
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'honeycomb');

        // publish translations
        $this->publishes([
            __DIR__ . '/lang' => base_path('resources/lang/vendor/honeycomb'),
        ], 'lang');

        // register custom response macros
        response()->macro('api',
            function ($status, $name, $data = null, $feedback = null, $metadata = [], $headers = []) {
                return ApiResponse::success($status, $name, $data, $feedback, $metadata, $headers);
            });

        response()->macro('apiError',
            function (ApiException $exception, $headers = []) {
                return ApiResponse::failure($exception, $headers);
            });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // register config file
        $this->mergeConfigFrom(
            __DIR__ . '/config/honeycomb.php', 'honeycomb'
        );

        // bind ApiExceptionWrapper
        $wrapperClass = config('honeycomb.api_exception_wrapper_class');

        // use default implementation if invalid
        if (!is_subclass_of($wrapperClass, ApiExceptionWrapperContract::class)) {
            $wrapperClass = ApiExceptionWrapper::class;
        }

        $this->app->bind(ApiExceptionWrapperContract::class, $wrapperClass);
    }

}
