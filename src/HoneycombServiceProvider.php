<?php

namespace Honeycomb;

use Illuminate\Support\ServiceProvider;

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
            __DIR__.'/config/honeycomb.php' => config_path('honeycomb.php'),
        ]);

        // load translations
        $this->loadTranslationsFrom(__DIR__.'/lang', 'honeycomb');

        // publish translations
        $this->publishes([
            __DIR__.'/lang' => base_path('resources/lang/vendor/honeycomb'),
        ]);
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
            __DIR__.'/config/honeycomb.php', 'honeycomb'
        );
    }

}
