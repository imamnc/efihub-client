<?php

namespace Efihub;

use Illuminate\Support\ServiceProvider;

class EfihubServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->bound('config')) {
            $this->mergeConfigFrom(__DIR__ . '/../config/efihub.php', 'efihub');
        }

        $this->app->singleton(EfihubClient::class, function () {
            return new EfihubClient();
        });
    }

    public function boot()
    {
        if (
            function_exists('config_path')
            && method_exists($this, 'publishes')
            && method_exists($this->app, 'runningInConsole')
            && $this->app->runningInConsole()
        ) {
            $this->publishes([
                __DIR__ . '/../config/efihub.php' => config_path('efihub.php'),
            ], 'config');
        }
    }
}
