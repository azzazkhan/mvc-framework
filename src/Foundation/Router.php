<?php

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Http\Request;

class Router
{
    /**
     * The HTTP routes registered with this router.
     * 
     * @var array<string, array>
     */
    protected array $routes = [];

    /**
     * Creates a new HTTP router instance.
     * 
     * @return self
     */
    public function __construct()
    {
        // 
    }

    /**
     * Add a request handler for GET HTTP request for the specified path.
     * 
     * @param  string  $path
     * @param  callable|string  $callback
     * @return self
     */
    public function get(string $path, callable|string $callback): self
    {
        $this->routes['get'][$path] = $callback;

        return $this;
    }

    /**
     * Resolves the current URL and returns appropriate response.
     * 
     * @return void
     */
    public function resolve(Request $request): mixed
    {
        $callback = $this->routes[$request->method][$request->path] ?? null;

        if ($callback instanceof Closure)
            return app()->call($callback);

        if (is_string($callback))
            return $this->render($callback);

        return 404;
    }

    public function render(string $view)
    {
        include_once sprintf('%s/views/%s.php', app('base_path'), $view);
    }
}
