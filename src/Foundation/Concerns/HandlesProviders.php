<?php

namespace Illuminate\Foundation\Concerns;

trait HandlesProviders
{
    use RegistersProviders;

    /**
     * The application service providers.
     * 
     * @var array<int, array>
     */
    protected array $providers = [];

    /**
     * Merge all application's service providers into a single list.
     * 
     * @return void
     */
    private function mergeServiceProviders(): void
    {
        // Get all declared service providers from the application
        // configuration repository which are not already registered
        // within the application
        $app_providers = array_filter(
            config('app.providers', []),
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
    }

    /**
     * Resolve the application's service provider instances and cache them. 
     * 
     * @return void
     */
    private function resolveServiceProviders(): void
    {
        foreach ($this->providers as $provider => $config) {
            if (!$config['instance'])
                $this->providers[$provider]['instance'] = $this->make($provider);
        }
    }

    /**
     * Call the "register" method on all service providers.
     * 
     * @return void
     */
    private function registerServiceProviders(): void
    {
        foreach ($this->providers as $provider => $config)
            if (!$config['registered']) {
                $this->call([$config['instance'], 'register']);
                $this->providers[$provider]['registered'] = true;
            }

        // If any service provider registered a new service provider into the
        // application then we need to resolve and call their "register" method
        // as well. We'll need to checking for such cases
        // foreach ($this->providers as $provider => $config) {
        //     if (!$config['instance'] || !$config['registered']) {
        //         $this->bootServiceProviders(true);

        //         // Break the loop on first case, as we need to iterate over all
        //         // service providers again to check their resolution and
        //         // registration status
        //         break;
        //     }
        // }
    }

    /**
     * Call the "boot" method on all service providers.
     * 
     * @return void
     */
    private function bootServiceProviders(): void
    {
        foreach ($this->providers as $provider => $config)
            if (!$config['booted']) {
                $this->call([$config['instance'], 'boot']);
                $this->providers[$provider]['booted'] = true;
            }
    }
}
