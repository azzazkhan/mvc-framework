<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Database;

/**
 * @method static \Illuminate\Contracts\Database\Connection connection(string $connection)
 * @method static \Illuminate\Database\Query\Builder table(string $table)
 * @method static \Illuminate\Database\Query\Builder method(mixed param)
 * 
 * @see \Illuminate\Database\Query\Builder
 */
class DB extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Database::class;
    }
}
