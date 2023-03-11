<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Contracts\Database\Query\Builder;
use PDO;

interface ConnectionInterface
{
    /**
     * Initiates new database connection.
     * 
     * @param  array  $config
     * @param  array  $options
     */
    public function __construct(array $config);

    /**
     * Prepares and executes the provided query.
     * 
     * @param  string  $query
     * @param  array  $params
     * @return mixed
     */
    public function query(string $query, array $params = []): \PDOStatement;

    /**
     * Returns the underling PDO connection.
     * 
     * @return \PDO
     */
    public function getPdo(): PDO;

    /**
     * Generates new query.
     * 
     * @param  string  $method
     * @param  array  $params
     */
    public function buildQuery(string $method, array $params = []): Builder;
}
