<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        // MySQL 5.7 compatibility: default string index length
        // utf8mb4 pada MySQL 5.7 butuh max 191 char untuk kolom index
        Schema::defaultStringLength(191);
    }
}
