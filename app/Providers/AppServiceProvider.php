<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PurifyService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PurifyService::class, function ($app) {
            return new PurifyService();
        });
    }

    public function boot()
    {
        //
    }
}

