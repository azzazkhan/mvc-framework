<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class Router
{
    use Routable;

    /**
     * The HTTP routes registered with this router.
     * 
     * @var array<string, array>
     */
    protected array $routes = [];

    /**
     * Namespace to append before controllers.
     * 
     * @var string
     */
    protected string $namespace = 'App\\Http\\Controllers';

    /**
     * Unwraps and resolves callback recursively.
     * 
     * @param  mixed  $callback
     * @return mixed
     */
    private function resolveCallback(mixed $callback)
    {
        if (is_callable($callback) || is_string($callback)) {
            return app()->call($callback);
        }

        return $callback;
    }

    /**
     * Resolves the current URL and returns appropriate response.
     * 
     * @return mixed
     */
    public function resolve(Request $request)
    {
        // If callback is a class method or closure, then resolve it using the
        // container and do further processing on returned results
        $callback = $this->resolveCallback(
            $this->routes[$request->method][$request->path] ?? null
        );

        if (!$callback) {
            app(Response::class)->setStatus(404);
            return print(view('errors.404')->layout('app')->render());
        }

        // View template provided, render the compiled view
        if ($callback instanceof View) {
            return print($callback->render());
        }

        // Convert arrays to JSON and send back a valid JSON response
        if (is_array($callback)) {
            header('Content-Type: application/json');
            return print(json_encode($callback, JSON_PRETTY_PRINT));
        }

        if (is_string($callback) || is_numeric($callback) || is_bool($callback)) {
            return print($callback);
        }

        if (is_null($callback)) return;

        // TODO: Handle other types of response, such as JSON, strings, files,
        // TODO: downloads, redirects and empty responses.
        dd($callback);
    }
}
