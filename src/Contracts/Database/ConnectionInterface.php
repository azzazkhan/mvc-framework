<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Contracts\Database\Query\Builder;

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
     * Prepares the provided query.
     * 
     * @param  string  $query
     * @return \PDOStatement|false
     */
    public function query(string $query): \PDOStatement|false;

    /**
     * Queries the provided query without preparation.
     * 
     * @param  string  $query
     * @return \PDOStatement|false
     */
    public function unprepared(string $query): \PDOStatement|false;

    /**
     * Returns the underling PDO connection.
     * 
     * @return \PDO
     */
    public function getPdo(): \PDO;

    /**
     * Generates new query.
     * 
     * @param  string  $method
     * @param  array  $params
     * @return mixed
     */
    public function buildQuery(string $method, array $params = []): Builder;
}
