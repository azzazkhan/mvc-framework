<?php

namespace Illuminate\Foundation;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Config\Repository as ConfigRepository;

class Application extends Container
{
    /**
     * The application service providers.
     * 
     * @var array<string, array>
     */
    protected array $providers = [];

    /**
     * Creates new application instance.
     */
    public function __construct(string $base_path)
    {
        $this->singleton('base_path', fn () => $base_path);
        $this->registerBaseBindings();
        $this->bootstrapServiceProviders();
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
        $this->singleton(ConfigRepository::class);

        $this->instance('config', app(ConfigRepository::class));
    }

    /**
     * Bootstrap all the application's service providers.
     * 
     * @return void
     */
    protected function bootstrapServiceProviders(): void
    {
        // Get all declared service providers from the application
        // configuration repository
        $serviceProviders = config('app.providers');

        // The resolved application service provider instances
        $providers = [];

        // Resolve all the service providers using the container
        foreach ($serviceProviders as $provider) {
            $providers[] = $this->make($provider);
        }

        // First call the "register" method on all providers so they first
        // bind any application services into the application's global
        // service container for later dependency resolution uses.
        foreach ($providers as $provider) {
            $this->call([$provider, 'register']);
        }

        // After all services have been bound to the container then call any
        // application services by calling the "boot" method of providers
        foreach ($providers as $provider) {
            $this->call([$provider, 'boot']);
        }

        $this->providers = $providers;
    }

    /**
     * Run the application services and route resolution.
     * 
     * @return void
     */
    public function run(): void
    {
        $this->call('Illuminate\\Routing\\Router@resolve');
    }
}
