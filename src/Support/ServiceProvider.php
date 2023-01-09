<?php

namespace Illuminate\Support;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

abstract class ServiceProvider
{
    /**
     * The current application container instance.
     * 
     * @var \Illuminate\Foundation\Application
     */
    protected Application $app;

    /**
     * Creates new service provider class.
     * 
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
    }

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
     * Call any application services.
     * 
     * @return void
     */
    public function boot()
    {
        // 
    }
}
