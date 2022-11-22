<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;
use Illuminate\View\View;

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
     * @param  \Illuminate\View\View|callable|string  $callback
     * @return self
     */
    public function get(string $path, View|callable|string $callback): self
    {
        $this->routes['get'][$path] = $callback;

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

    /**
     * Resolves HTTP routes.
     * 
     * @return void
     */
    private function resolvePaths(): void
    {
        require_once app('base_path') . '/routes.php';
    }

    /**
     * Resolves the current URL and returns appropriate response.
     * 
     * @return void
     */
    public function resolve(Request $request): mixed
    {
        // Resolve path definitions and populate route bindings
        $this->resolvePaths();

        $callback = $this->routes[$request->method][$request->path] ?? null;

        // View template provided
        if ($callback instanceof View) {
            return $callback->render();
        }

        // Controller method or Class@method string provided
        if (is_callable($callback) || is_string($callback)) {
            return app()->call($callback);
        }

        return 404;
    }
}
