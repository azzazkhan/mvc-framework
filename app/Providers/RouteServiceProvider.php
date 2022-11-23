<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application service bindings.
     * 
     * @return void
     */
    public function register()
    {
        // 
    }

    /**
     * Load any required application services.
     * 
     * @return void
     */
    public function boot()
    {
        require_once app('base_path') . '/routes.php';
    }
}
