<?php

namespace Illuminate\Http;

class Request
{
    /**
     * The current URL path being handled by the application.
     * 
     * @return string
     */
    public function path(): string
    {
        return explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
    }

    /**
     * The method of incoming HTTP request.
     * 
     * @return string 
     */
    public function method(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Magic method override for accessing class method as properties.
     * 
     * @param  string  $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        if (method_exists($this, $key))
            return $this->{$key}();
    }
}
