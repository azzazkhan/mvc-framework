<?php

namespace Illuminate\Config;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Repository
{
    /**
     * The application service container.
     * 
     * @return \Illuminate\Container\Container
     */
    protected Container $app;

    /**
     * The parsed application configuration.
     * 
     * @var array
     */
    private array $items = [];

    /**
     * Mark the repository as bootstrapped.
     * 
     * @var bool
     */
    private bool $bootstrapped = false;

    /**
     * Creates new configuration repository.
     * 
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
        $this->bootstrap();
    }

    /**
     * Bootstrap the application configuration files.
     * 
     * @return void
     */
    private function bootstrap()
    {
        // If all config files already have been parsed and loaded then skip it
        if ($this->bootstrapped) return;

        // Load each config file, prase and store content in config repository
        foreach (File::glob(sprintf('%s/config/*.php', app('base_path'))) as $filepath) {
            $value = File::getRequire($filepath);

            if (!is_array($value)) continue;

            $this->items[File::name($filepath)] = $value;
        }

        // Mark the repository as bootstrapped
        $this->bootstrapped = true;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }
}
