<?php

namespace Illuminate\Foundation\Concerns;

trait RegistersProviders
{
    /**
     * The application's essential service providers.
     * 
     * @var array<string>
     */
    private array $appProviders = [];

    /**
     * Registers the essential service providers into the application.
     * 
     * @return void
     */
    protected function registerBaseProviders(): void
    {
        foreach ($this->appProviders as $provider)
            $this->register($provider);
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
        if (!array_key_exists($provider, $this->providers))
            $this->providers[$provider] = [
                'instance' => null,
                'registered' => false,
                'booted' => false
            ];
    }
}
