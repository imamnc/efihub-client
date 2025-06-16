<?php

namespace Efihub;

use Illuminate\Support\ServiceProvider;

class EfihubServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/efihub.php', 'efihub');

        $this->app->singleton(EfihubClient::class, function () {
            return new EfihubClient();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/efihub.php' => config_path('efihub.php'),
        ], 'config');
    }
}
