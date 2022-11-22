<?php

namespace Illuminate\Routing;

use Illuminate\View\View;

trait Routable
{

    /**
     * Add a request handler for GET HTTP request for the specified path.
     * 
     * @param  string  $path
     * @param  \Illuminate\View\View|callable|string  $callback
     * @return self
     */
    public function get(string $path, View|array|callable|string $callback): self
    {
        $this->routes['get'][$path] = $this->resolveController($callback);

        return $this;
    }

    /**
     * Add a request handler for GET HTTP request for the specified path.
     * 
     * @param  string  $path
     * @param  \Illuminate\View\View  $view
     * @return self
     */
    public function view(string $path, View $view): self
    {
        $this->routes['get'][$path] = $view;

        return $this;
    }

    protected function resolveController(mixed $callback)
    {
        // Callback is controller method [class-string, 'method'] and we need
        // to resolve the controller class with dependency to make it a valid
        // callable
        if (is_array($callback)) {
            // This will now be a valid callable // [object, 'method']
            return [app()->make($callback[0]), $callback[1]];
        }

        // Controller Class::staticMethod or Class@method string provided,
        // prepend namespace if specified
        if (is_string($callback) && isset($this->namespace)) {
            // Now the callback string is a fully qualified class-string
            return sprintf('%s\\%s', $this->namespace, $callback);
        }

        return $callback;
    }
}
