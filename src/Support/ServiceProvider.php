<?php

namespace Illuminate\Support;

use Illuminate\Container\Container;

abstract class ServiceProvider
{
    /**
     * The current application container instance.
     * 
     * @var \Illuminate\Container\Container
     */
    protected Container $app;

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
