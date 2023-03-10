<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Connection;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;

class Builder implements BuilderContract
{
    /**
     * Database connection on which the query will execute.
     * 
     * @var \Illuminate\Contracts\Database\Connection
     */
    private Connection $connection;

    public function __construct(Connection &$connection)
    {
        $this->connection = $connection;
    }

    public function table(string $table): self
    {
        return $this;
    }

    public function greet()
    {
        return 'hello from query builder!';
    }
}
