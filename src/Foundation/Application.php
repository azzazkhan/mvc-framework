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
     * @var array<int, array>
     */
    protected array $providers = [];

    /**
     * Indicates the service providers should be resolved.
     * 
     * @var bool
     */
    protected bool $bootProviders = true;

    /**
     * Creates new application instance.
     */
    public function __construct(string $base_path)
    {
        $this->singleton('base_path', fn () => $base_path);
        $this->registerBaseBindings();
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
        $this->singleton(ConfigRepository::class);

        $this->instance('config', app(ConfigRepository::class));
    }

    /**
     * Bootstrap all the application's service providers.
     * 
     * @return void
     */
    protected function bootServiceProviders(): void
    {
        // We do not need to resolve the service providers
        if (!$this->bootProviders)
            return;

        // Get all declared service providers from the application
        // configuration repository which are not already registered
        // within the application
        $app_providers = array_filter(
            config('app.providers'),
            fn (string $provider) => !array_key_exists($provider, $this->providers)
        );

        // Merge the application service providers defined in the application
        // configuration repository with current list of already registered
        // application service providers so they can be resolved evenly
        foreach ($app_providers as $provider)
            $this->providers[$provider] = [
                'instance' => null,
                'registered' => false,
                'booted' => false
            ];

        // Resolve all the service providers using the container and cache
        // the instances for later registration and booting process
        foreach ($this->providers as $provider => $config) {
            if (!$config['instance'])
                $this->providers[$provider]['instance'] = $this->make($provider);
        }

        // First call the "register" method on all providers so they first
        // bind any application services into the application's global
        // service container for later dependency resolution uses.
        foreach ($this->providers as $provider => $config) {
            if (!$config['registered'])
                $this->call([$config['instance'], 'register']);
        }

        // If any service provider registered a new service provider into the
        // application then we need to resolve and call their "register" method
        // as well. We'll need to checking for such cases
        foreach ($this->providers as $provider => $config) {
            if (!$config['instance'] || !$config['registered']) {
                $this->bootServiceProviders();

                // Break the loop on first case, as we need to iterate over all
                // service providers again to check their resolution and
                // registration status
                break;
            }
        }

        // After all services have been bound to the container then call any
        // application services by calling the "boot" method of providers
        foreach ($this->providers as $provider) {
            if (!$config['booted'])
                $this->call([$config['instance'], 'boot']);
        }

        // All application service providers have been resolved, we do not need
        // to resolve the providers unless we are instructed explicitly, so
        // we will mark service providers resolution status as resolved
        $this->bootProviders = false;
    }

    /**
     * Register a new service provider into the application.
     * 
     * @param  string  $provider
     * @return void
     */
    public function register(string $provider): void
    {
        // Check if the application service provider has already been
        // registered in the application or not, because if it was already
        // resolved and we register it again then the resolved and booted
        // instance will be overridden which might introduce unexpected
        // behaviors
        if (!array_key_exists($provider, $this->providers)) {
            $this->providers[$provider] = [
                'instance' => null,
                'registered' => false,
                'booted' => false
            ];

            // Mark the application service providers resolution status as
            // unresolved
            $this->bootProviders = true;
        }
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
