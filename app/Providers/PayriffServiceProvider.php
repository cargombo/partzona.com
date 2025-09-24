<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PayriffService;

class PayriffServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PayriffService::class, function ($app) {
            return new PayriffService();
        });

        // Konfiqurasiya faylını birləşdiririk ki, config('payriff.key') işləsin
        $this->mergeConfigFrom(
            __DIR__.'/../../config/payriff.php', 'payriff'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Konfiqurasiya faylını `php artisan vendor:publish` üçün əlçatan edirik
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/payriff.php' => config_path('payriff.php'),
            ], 'config');
        }
    }
}
