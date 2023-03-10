<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Routing\Router get(string $path, \Illuminate\View\View|callable|string $callback)
 * @method static \Illuminate\Routing\Router view(string $path, \Illuminate\View\View $view)
 * 
 * @see \Illuminate\Routing\Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
