<?php

use Illuminate\Container\Container;
use Illuminate\Support\Asset\Vite;
use Illuminate\View\View;
use Packages\DotEnv\DotEnv;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('base_path')) {
    /**
     * Gets the absolute path to the application.
     * 
     * @param  string|null  $path
     * @return string
     */
    function base_path(string $path = null): string
    {
        return join_path(app('base_path'), $path);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed|\Illuminate\Config\Repository
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('database_path')) {
    /**
     * Gets the absolute path to application's database files.
     * 
     * @param  string|null  $path
     * @return string
     */
    function database_path(string $path = null): string
    {
        return join_path(base_path('database'), $path);
    }
}

if (!function_exists('dd')) {
    /**
     * Dumps the provided value and exists the script execution.
     * 
     * @param  mixed  $value
     * @return void
     */
    function dd(mixed $value): void
    {
        print('<pre>');
        var_dump($value);
        print('</pre>');
        exit(1);
    }
}

if (!function_exists('e')) {
    /**
     * Encode HTML special characters in a string.
     *
     * @param  string|null  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    function e(string|null $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('env')) {
    /**
     * Get an entry from the system environment.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return string
     */
    function env(string $key = null, mixed $value = null): mixed
    {
        if (is_null($key))
            return app(DotEnv::class);

        return app(DotEnv::class)->get($key, $value);
    }
}

if (!function_exists('join_path')) {
    /**
     * Join multiple path strings together.
     * 
     * @param  array  $paths
     * @return string
     */
    function join_path(...$paths): string
    {
        // dd($paths);

        if (count($paths) == 0)
            return $paths[0];

        $path_strings = [];

        for ($i = 0; $i < count($paths); $i++) {
            // Do not alter the first element and use it as is
            if ($i == 0) {
                $path_strings[] = $paths[$i];
                continue;
            }

            $path = $paths[$i];

            // If path is an empty string then do not concat it
            if (!$path || $path == '/')
                continue;

            // Remove the leading slash from the path string
            elseif ($path[0] == '/')
                $path = substr($path, 1, strlen($paths[$i]));

            // Remove the trailing slash from the path string
            elseif ($path[strlen($path) - 1] == '/')
                $path = substr($paths[$i], 0, strlen($paths[$i]) - 1);

            $path_strings[] = $path;
        }

        $paths = array_filter($paths, fn ($path) => is_string($path) && strlen($path) > 0);

        return str_replace('\\', '/', implode('/', $path_strings));
    }
}

if (!function_exists('optional')) {
    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function optional(
        mixed $value = null,
        callable $callback = null,
        mixed $default = null
    ): mixed {
        if (is_null($callback)) {
            return $default;
        } elseif (!is_null($value)) {
            return app()->call($callback, [$value]);
        }
    }
}

if (!function_exists('random_str')) {
    /**
     * Generate a random string, using a cryptographically secure pseudorandom
     * number generator (random_int)
     *
     * This function uses type hints now (PHP 7+ only), but it was originally
     * written for PHP 5 as well.
     * 
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     * 
     * @param  int  $length      How many characters do we want?
     * @param  string  $keyspace A string of all possible characters
     *                           to select from
     * @return string
     */
    function random_str(
        int $length = 64,
        string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        if ($length < 1) {
            throw new \RangeException("Length must be a positive integer");
        }
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Gets the absolute path to application's resources directory.
     * 
     * @param  string|null  $path
     * @return string
     */
    function resource_path(string $path = null): string
    {
        return join_path(base_path('resources'), $path);
    }
}

if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param  int|array  $times
     * @param  callable  $callback
     * @param  int|\Closure  $sleepMilliseconds
     * @param  callable|null  $when
     * @return mixed
     *
     * @throws \Exception
     */
    function retry($times, callable $callback, $sleepMilliseconds = 0, $when = null)
    {
        $attempts = 0;

        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;

            $times = count($times) + 1;
        }

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1 || ($when && !$when($e))) {
                throw $e;
            }

            $sleepMilliseconds = $backoff[$attempts - 1] ?? $sleepMilliseconds;

            if ($sleepMilliseconds) {
                usleep(value($sleepMilliseconds, $attempts, $e) * 1000);
            }

            goto beginning;
        }
    }
}

if (!function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     *
     * @param  mixed  $condition
     * @param  \Throwable|string  $exception
     * @param  mixed  ...$parameters
     * @return mixed
     *
     * @throws \Throwable
     */
    function throw_if($condition, $exception = 'RuntimeException', ...$parameters)
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw is_string($exception) ? new RuntimeException($exception) : $exception;
        }

        return $condition;
    }
}

if (!function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     *
     * @param  mixed  $condition
     * @param  \Throwable|string  $exception
     * @param  mixed  ...$parameters
     * @return mixed
     *
     * @throws \Throwable
     */
    function throw_unless($condition, $exception = 'RuntimeException', ...$parameters)
    {
        throw_if(!$condition, $exception, ...$parameters);

        return $condition;
    }
}

if (!function_exists('uuidv4')) {
    /**
     * Generates a v4 UUID using custom implementation.
     * 
     * @return string
     */
    function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('view')) {
    /**
     * Creates a new view instance.
     * 
     * @param  string  $path
     * @param  array  $data
     * @return \Illuminate\View\View
     */
    function view(string $path, array $data = []): View
    {
        return new View($path, $data);
    }
}

if (!function_exists('vite')) {
    /**
     * Prints import tags for specified scripts
     */
    function vite(string|array $assets)
    {
        Vite::render($assets);
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @template TValue
     *
     * @param  TValue  $value
     * @param  (callable(TValue): TValue)|null  $callback
     * @return TValue
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}
