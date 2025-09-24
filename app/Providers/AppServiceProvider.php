<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Config;



class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
      Schema::defaultStringLength(191);
      Paginator::useBootstrap();
      if (env('APP_ENV') === 'production') {
          \URL::forceScheme('https');
      }

      DB::listen(function (QueryExecuted $event) {
          if ($event->time >= 3000) {
              try {
                  sendTelegram(json_encode([
                      "SlowQuery " . md5($event->sql),
                      "Time : " . $event->time . " ms // Query: " . vsprintf(str_replace('?', '%s', $event->sql), $event->bindings)
                  ]));
              } catch (\Exception $exception) {
                  sendTelegram(json_encode(["Error " . $exception->getMessage()]));
              }
          }
      });
      if (request()->ip() === '127.0.0.1') {
          Config::set('app.debug', true);
      }

  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }
}
