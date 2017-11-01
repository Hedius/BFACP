<?php

namespace BFACP\Providers;

use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * @package BFACP\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Resource::withoutWrapping();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production' && class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
