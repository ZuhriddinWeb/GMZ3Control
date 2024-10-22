<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ValuesParameters;
use App\Observers\ValuesParametersObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ValuesParameters::observe(ValuesParametersObserver::class);
        // \App\Models\GraphicTimes::observe(\App\Observers\GraphicTimesObserver::class);
    }
}
