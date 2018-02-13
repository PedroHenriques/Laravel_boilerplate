<?php

namespace App\Providers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
  }
  
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    // Used to avoid a Laravel 5.6 exception: 'Class hash.driver does not exist'
    $this->app->singleton(Hasher::class, HashManager::class);
  }
}
