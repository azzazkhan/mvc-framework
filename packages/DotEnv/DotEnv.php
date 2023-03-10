<?php

namespace Packages\DotEnv;

class DotEnv
{
    private string $file;
    private string $path;

    private array $entries = [];
    private array $references = [];

    public function __construct(string $path, string $file = '.env')
    {
        $this->path = $path;
        $this->file = $file;

        $this->parse();
    }

    /**
     * Get an entry from the environment data.
     * 
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? getenv($key) ?: $this->get_env($key, $default);
    }

    /**
     * Get an entry from the local environment file.
     * 
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get_env(string $key, mixed $default = null): mixed
    {
        if (isset($this->references[$key]))
            return $this->get($this->references[$key], $default);

        return $this->entries[$key] ?? $default;
    }

    private function parse()
    {
        foreach (explode("\n", file_get_contents(join_path($this->path, $this->file))) as $line) {
            $entry = static::parseEntry($line);

            if (!$entry) continue;

            [$key, $value, $reference] = $entry;

            if ($reference)
                $this->references[$key] = $reference;

            else
                $this->entries[$key] = $value;
        }
    }

    private static function parseEntry(string $entry): ?array
    {
        $line = trim($entry);

        // Get the key name from the string
        $matches = [];
        preg_match('/^([A-Za-z_][A-Za-z0-9_]+=)/', $line, $matches);

        // No matches were found in the string!
        if (!$matches) return null;

        // Extract key name (omit the leading equal sign) and value of the key
        $key = substr($line, 0, strlen($matches[0]) - 1);
        $value = trim(substr($line, strlen($matches[0])));

        // Check if the value references another key
        $matches = [];
        preg_match('/^"\${([A-Za-z0-0_]+)}"$/', $value, $matches);

        // If the value references another key then grab the reference key name
        $references = $matches && $matches[1] ? $matches[1] : null;

        // Set null if defined
        if ($value == "null")
            $value = null;

        // Set value to empty string if not defined (prevent nullish coalescing)
        else if (!$value)
            $value = "";

        // Remove the wrapping quotation marks the value was enclosed in
        else if ($value[0] == '"' && $value[strlen($value) - 1] == '"')
            $value = trim($value, '"');

        return [$key, $value, $references];
    }
}
