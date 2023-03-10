<?php

namespace Illuminate\Database;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Database;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application service bindings.
     * 
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Database::class);
        $this->app->singleton('database', function (Container $app) {
            $app->make(Database::class);
        });
    }

    /**
     * Load any required application services.
     * 
     * @return void
     */
    public function boot()
    {
        // 
    }
}
