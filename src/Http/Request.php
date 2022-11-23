<?php

namespace Illuminate\Http;

class Request
{
    /**
     * The items in query string of current request.
     * 
     * @var array<string, mixed>
     */
    protected $queries = [];

    /**
     * The items in query string of current request.
     * 
     * @var array<string, mixed>
     */
    protected $inputs = [];

    /**
     * The items in query string of current request.
     * 
     * @var array<string, array>
     */
    protected $files = [];

    public function __construct()
    {
        $this->getQueries();
        $this->getInputs();
    }

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
     * Get the input value from request's query string.
     * 
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->queries) ? $this->queries[$key] : $default;
    }

    /**
     * Get the input value from request's body.
     * 
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->inputs) ? $this->inputs[$key] : $default;
    }

    /**
     * Get the data from current request.
     * 
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->inputs))
            return $this->inputs[$key];

        if (array_key_exists($key, $this->queries))
            return $this->queries[$key];

        return $default;
    }

    /**
     * Gets all data from the current request.
     * 
     * @return array
     */
    public function all(): array
    {
        return [
            ...$this->queries,
            ...$this->inputs,
        ];
    }

    /**
     * Fetch and parse query string of current request.
     * 
     * @return void
     */
    protected function getQueries()
    {
        foreach ($_GET as $key => $value) {
            $this->queries[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    /**
     * Fetch and parse the body of current request.
     * 
     * @return void
     */
    protected function getInputs()
    {
        if (preg_match('/(post|put|patch)/', $this->method())) {
            foreach ($_POST as $key => $value) {
                $this->inputs[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
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

        if (!is_null($data = $this->get($key)))
            return $data;
    }
}
