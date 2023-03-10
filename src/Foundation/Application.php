<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Concerns\HandlesProviders;
use Packages\DotEnv\DotEnv;

class Application extends Container
{
    use HandlesProviders;

    /**
     * Creates new application instance.
     * 
     * @param  string  $base_path
     * @throws \Exception
     */
    public function __construct(string $base_path)
    {
        if (!$base_path)
            throw new Exception('No [base_path] was provided!');

        $this->singleton('base_path', fn () => $base_path);

        $this->registerBaseBindings();
        $this->registerBaseProviders();


        // Merge the application service providers defined in the application
        // configuration repository with current list of already registered
        // application service providers so they can be resolved evenly
        $this->mergeServiceProviders();

        // Resolve all the service providers using the container and cache
        // the instances for later registration and booting process
        $this->resolveServiceProviders();

        // First call the "register" method on all providers so they first
        // bind any application services into the application's global
        // service container for later dependency resolution uses.
        $this->registerServiceProviders();

        // After all services have been bound to the container, call the "boot"
        // method of all service providers which will boot up the application
        $this->bootServiceProviders();
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
        $this->instance(Container::class, $this);

        // Critical application bindings
        $this->singleton(Router::class);
        $this->singleton(Request::class);
        $this->singleton(Response::class);


        $this->singleton(DotEnv::class, fn () => new DotEnv(base_path()));
        $this->singleton(ConfigRepository::class);

        // Named bindings
        $this->instance('config', app(ConfigRepository::class));
        $this->singleton('router', fn (Container $app) => $app->make(Router::class));
        $this->singleton('request', fn (Container $app) => $app->make(Request::class));
        $this->singleton('response', fn (Container $app) => $app->make(Response::class));
    }

    /**
     * Run the application services and route resolution.
     * 
     * @return void
     */
    public function run(): void
    {
        // TODO: Migrate application request booting to HTTP Kernel
        $this->call('Illuminate\\Routing\\Router@resolve');
    }
}
