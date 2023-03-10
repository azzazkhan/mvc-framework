<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Contracts\Database\Query\Builder;

interface Connection
{
    /**
     * Initiates new database connection.
     * 
     * @param  array  $config
     * @param  array  $options
     */
    public function __construct(array $config);

    /**
     * Executes the provided query.
     * 
     * @param  string  $query
     * @param  array  $params
     * @return mixed
     */
    public function query(string $query, array $params = []): mixed;

    /**
     * Prepare the query for execution.
     * 
     * @param  string  $query
     * @param  array  $params
     * @return mixed
     */
    public function prepare(string $query, array $params = []): mixed;

    /**
     * Generates new query.
     * 
     * @param  string  $method
     * @param  array  $params
     */
    public function __call(string $method, array $params = []): Builder;
}
