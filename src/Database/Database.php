<?php

namespace Illuminate\Database;

use Illuminate\Contracts\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Database\Connections\{
    MySQLConnection,
    PostgresConnection,
    MSSQLConnection,
    SQLiteConnection
};

class Database
{
    protected const AVAILABLE_DRIVERS = [
        'mysql', 'mssql', 'postgres', 'sqlite'
    ];

    /**
     * Active database connections.
     * 
     * @var array<\Illuminate\Contracts\Database\ConnectionInterface>
     */
    protected array $connections = [];

    /**
     * Constructs and caches the specified database connection.
     * 
     * @param  string|null  $connection
     * @return \Illuminate\Contracts\Database\ConnectionInterface
     * @throws \InvalidArgumentException
     */
    public function connection(string $connection = null): ConnectionInterface
    {
        $connection = $connection ?: config('database.default');

        // If the connection is already established then return the cached
        // connection instead of initiating a new one
        if ($this->connections[$connection] ?? false)
            return $this->connections[$connection];

        // Retrieve connection configuration
        $config = config("database.connections.{$connection}");

        // Validate the connection configuration
        if (!$config || !is_array($config))
            throw new InvalidArgumentException(
                "The database connection [$connection] is not configured! Did you forgot to add the connection in [config/database.php]?"
            );

        $driver = Arr::get($config, 'driver');

        // Validate connection driver
        if (!$driver)
            throw new InvalidArgumentException("The database connection [$connection] has no driver specified!");

        // Create new database connection with appropriate database driver and
        // use the cached connection for subsequent requests
        $this->connections[$connection] = match ($driver) {
            'mysql' => new MySQLConnection($config),
            'pgsql' => new PostgresConnection($config),
            'mssql' => new MSSQLConnection($config),
            'sqlite' => new SQLiteConnection($config),
            default => throw new InvalidArgumentException("The database connection [$connection] uses unsupported driver!")
        };

        return $this->connections[$connection];
    }

    /**
     * Get the cached database connections.
     * 
     * @return array<\Illuminate\Contracts\Database\ConnectionInterface>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Forward calls to default database connection.
     * 
     * @param  string  $method
     * @param  array  $parameters
     * 
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->buildQuery($method, $parameters);
    }
}
