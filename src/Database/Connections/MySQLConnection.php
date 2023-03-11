<?php

namespace Illuminate\Database\Connections;

use Illuminate\Contracts\Database\ConnectionInterface;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;
use PDO;
use PDOStatement;

class MySQLConnection implements ConnectionInterface
{
    /**
     * The active connection.
     * 
     * @var \PDO
     */
    protected PDO $connection;

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

        $this->connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query): PDOStatement|false
    {
        $statement = $this->connection->prepare($query);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function unprepared(string $query): PDOStatement|false
    {
        $statement = $this->connection->query($query);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function getPdo(): PDO
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function buildQuery(string $method, array $params = []): BuilderContract
    {
        return (new Builder($this))->{$method}(...$params);
    }
}
