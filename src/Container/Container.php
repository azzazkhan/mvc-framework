<?php

namespace Illuminate\Container;

use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container implements ContainerContract
{
    /**
     * The current globally available container (if any).
     *
     * @var static 
     */
    protected static Container $instance;

    /**
     * The container's bindings.
     * 
     * @var array<array>
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     * 
     * @var array<mixed>
     */
    protected array $instances = [];

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind(
        string $abstract,
        Closure|string|null $concrete = null,
        $shared = false
    ): void {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete))
            $concrete = $abstract;

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf(
        string $abstract,
        Closure|string|null $concrete = null,
        bool $shared = false
    ): void {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singletonIf(string $abstract, Closure|string|null $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        return $this->instances[$abstract] = $instance;
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array<string, mixed>  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function call(
        callable|string $callback,
        array $parameters = [],
        string|null $defaultMethod = null
    ): mixed {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * Resolve the given type from the container.
     * 
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     * 
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {

        if ($this->bound($abstract)) {

            // The requested binding is registered as singleton and already
            // exists in cache, return the cached instance
            if (isset($this->instances[$abstract]) && !$parameters)
                return $this->instances[$abstract];


            $concrete = $this->getConcrete($abstract);

            // A factory function was bound as concrete implementation for
            // the registered abstract binding, call the factory method
            // and pass the container instance for further resolving
            if (is_callable($concrete)) {
                $instance = $concrete($this);
            } else {
                // Concrete is a fully qualified class name, resolve it
                // using the container and save the instance
                $instance = $this->resolve($concrete, $parameters);
            }


            // If the abstract is a singleton then cache the built concrete
            // for subsequent requests
            if (!$parameters && $this->isShared($abstract)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        // The abstract is not bound in the container
        return $this->resolve($abstract, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function get(string $id)
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }

            throw new EntryNotFoundException($id, is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $class = new \ReflectionClass($abstract);

        // Make sure the class is instantiable
        if (!$class->isInstantiable())
            throw new BindingResolutionException(
                sprintf('The class "%s" is not instantiable', $abstract)
            );


        $constructor = $class->getConstructor();

        if (!$constructor) // No constructor, means zero dependencies
            return new $abstract;

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // The value for parameter is explicitly provided
            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];

                unset($parameters[$name]);
            }

            // Default value is already provided, use it instead
            elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            }

            // No type hint was provided
            // elseif (!$type) {
            //     throw new BindingResolutionException(
            //         sprintf(
            //             'Failed to resolve class "%s" because param "%s" has no type hint',
            //             $abstract,
            //             $name
            //         )
            //     );
            // }

            // We currently do not support union type dependencies resolution 
            // elseif ($type instanceof \ReflectionUnionType) {
            //     throw new BindingResolutionException(
            //         sprintf(
            //             'Failed to resolve class "%s" because param "%s" has a union type',
            //             $abstract,
            //             $name
            //         )
            //     );
            // }

            // Class dependency specified with a type hint, resolve it using
            // the container
            elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            }

            // Cannot resolve method/function dependency
            else {
                throw new BindingResolutionException(
                    sprintf(
                        'Unable to resolve dependency [%s] in %s',
                        $name,
                        $param->getDeclaringClass()?->getName() ?: 'Closure'
                    )
                );
            }
        }

        return $class->newInstanceArgs(array_values(array_merge($dependencies, array_values($parameters))));
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string  $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Drop all of the stale instances.
     * 
     * @param  string  $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * @return void
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of container.
     * 
     * @param  \Illuminate\Container\Container  $container
     * @return \Illuminate\Container\Container|null
     */
    public static function setInstance(Container $container = null): ?Container
    {
        return static::$instance = $container;
    }
}
