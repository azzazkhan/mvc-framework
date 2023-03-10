<?php

namespace Illuminate\Database\Connections;

use Illuminate\Contracts\Database\Connection;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;
use PDO;

class MySQLConnection implements Connection
{
    /**
     * The active connection.
     * 
     * @var \PDO $connection
     */
    private PDO $connection;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config)
    {
        $this->connection = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            ),
            $config['username'],
            $config['password'],
            \CStr::isValidArray($config['options']) ? $config['options'] : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, array $params = []): mixed
    {
        // 
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $query, array $params = []): mixed
    {
        // 
    }

    /**
     * {@inheritdoc}
     */
    public function __call(string $method, array $params = []): BuilderContract
    {
        return (new Builder($this))->{$method}(...$params);
    }
}
