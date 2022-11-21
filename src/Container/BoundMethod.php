<?php

namespace Illuminate\Container;

use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

class BoundMethod
{
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    public static function call(
        Container $container,
        callable|string $callback,
        array $parameters = [],
        string|null $defaultMethod = null
    ): mixed {
        if (is_string($callback)) {
            // A static Class::method or non-static Class@method string is provided
            // we will parse it later but first we need to guess a default method
            // which we can call in case no method is provided in class-string
            if (!$defaultMethod && method_exists($callback, '__invoke'))
                $defaultMethod = '__invoke';

            // Prase the callable string and convert to callable array with 
            // appropriate class-string or resolved instance
            $callback = static::parseMethodName($container, $callback, $defaultMethod);
        }

        // Resolve dependencies and pass additional specified parameters
        return $callback(...array_values(static::getMethodDependencies($container, $callback, $parameters)));
    }

    /**
     * Parses the callable static Class::method, non-static Class@method or
     * class-string and returns appropriate callable array.
     * 
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $callable
     * @param  string|null  $defaultMethod
     * @return array
     * 
     * @throws \InvalidArgumentException
     */
    protected static function parseMethodName(
        Container $container,
        string $target,
        string|null $defaultMethod = null
    ): array {
        $method = $defaultMethod;
        $static = false;

        // Static method provided (Class::method)
        if (str_contains($target, '::') && $static = true)
            [$class, $method] = explode('::', $target);

        // Class method provided (Class@method)
        elseif (str_contains($target, '@')) {
            [$class, $method] = explode('@', $target);
        }

        // Only class name provided, need to fallback to default method
        else $class = $target;

        if (is_null($method))
            throw new InvalidArgumentException('Method not provided.');

        // If it was a non-static Class@method string or only class-string with
        // a defaultMethod then resolve the class through container and return
        // the [object, method] callable
        if (!$static)
            $class = $container->make($class);

        return [$class, $method];
    }

    /**
     * Get the proper reflection instance for the given callback.
     * 
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     * 
     * @throws \ReflectionException
     */
    protected static function getCallReflector(callable|string $callback): ReflectionFunctionAbstract
    {
        return is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable  $callback
     * @param  array  $parameters
     * @return array
     *
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected static function getMethodDependencies(
        Container $container,
        callable $callback,
        array $parameters = []
    ): array {
        $dependencies = [];

        foreach (static::getCallReflector($callback)->getParameters() as $param) {
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

            // Class dependency specified with a type hint, resolve it using
            // the container
            elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $container->make($type->getName());
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

        return array_merge($dependencies, array_values($parameters));
    }
}
