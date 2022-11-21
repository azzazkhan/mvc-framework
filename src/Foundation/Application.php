<?php

namespace Illuminate\Foundation;

use Illuminate\Container\Container;
use Illuminate\Http\Request;

class Application extends Container
{
    /**
     * Creates new application instance.
     */
    public function __construct(string $base_path)
    {
        $this->singleton('base_path', fn () => $base_path);
        $this->registerBaseBindings();
    }

    /**
     * Set globally available service container instance and register critical
     * application bindings.
     * 
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        // Store the application reference in globally available instance so
        // it can be accessed from anywhere also bind the current application
        // instance in the container itself to always receive the same instance
        static::setInstance($this);
        $this->instance('app', $this);

        // Critical application bindings
        $this->singleton(Router::class);
        $this->singleton(Request::class);
    }

    /**
     * Run the application services and route resolution.
     * 
     * @return void
     */
    public function run()
    {
        $this->call(Router::class, [], 'resolve');
    }
}
