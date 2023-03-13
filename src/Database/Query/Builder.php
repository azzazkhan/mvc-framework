<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\ConnectionInterface;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;

class Builder implements BuilderContract
{
    /**
     * The database connection instance.
     * 
     * @var \Illuminate\Contracts\Database\ConnectionInterface
     */
    protected ConnectionInterface $connection;

    /**
     * The prepared/unprepared PDO statement.
     * 
     * @var \PDOStatement
     */
    protected PDOStatement $statement;

    /**
     * The generated SQL query.
     *
     * @var array
     */
    protected string $query;

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    protected string $from;

    /**
     * The columns to be retrieved.
     * 
     * @var array
     */
    protected array $columns = ['*'];

    /**
     * The current query value bindings.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * @param  \Illuminate\Contracts\Database\ConnectionInterface  $connection
     */
    public function __construct(ConnectionInterface &$connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sets the table name for query.
     * 
     * @param  string  $table
     * @return self
     */
    public function table(string $table): self
    {
        $this->from = $table;

        return $this;
    }

    /**
     * Determine if the value is a query builder instance or a Closure.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isQueryable(mixed $value): bool
    {
        return $value instanceof self || $value instanceof \Closure;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery(): self
    {
        return new static($this->connection);
    }

    /**
     * Get the generated raw SQL.
     * 
     * @return string|null
     */
    public function toSql(): ?string
    {
        return $this->query ?: null;
    }

    /**
     * Get bindings for query.
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Dumps the query with bindings.
     * 
     * @return void
     */
    public function dd(): void
    {
        dd([$this->toSql(), $this->getBindings()]);
    }

    /**
     * Prepares the SQL query.
     * 
     * @param  string  $query
     * @param  array  $bindings
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(string $query, array $bindings = []): self
    {
        $this->statement = $this->connection->query($query);
        $this->bindings = $bindings;
        $this->query = $query;

        return $this;
    }

    /**
     * Builds unprepared SQL query.
     * 
     * @param  string  $query
     * @param  array  $bindings
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public function unprepared(string $query, array $bindings = []): self
    {
        $this->statement = $this->connection->unprepared($query);
        $this->bindings = $bindings;
        $this->query = $query;

        return $this;
    }

    /**
     * Fetches all records.
     * 
     * @return mixed
     */
    public function get(): mixed
    {
        // If no query was specified then get all records from the table
        if ((!isset($this->query) || !$this->query) && $this->from)
            $this->unprepared($this->getBaseSelectQuery());

        $this->run();

        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the first record.
     * 
     * @return mixed
     */
    public function first(): mixed
    {
        // If no query was specified then get all records from the table
        if ((!isset($this->query) || !$this->query) && $this->from)
            $this->unprepared($this->getBaseSelectQuery());

        $this->run();

        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Runs the query.
     * 
     * @param  bool  $silent
     * @return mixed
     * @throws \Exception
     */
    public function run(bool $silent = true): bool
    {
        try {
            if (is_assoc($this->bindings))
                return $this->statement->execute($this->bindings);

            // Bind all values as positional arguments into the statement
            $count = 1;
            foreach ($this->bindings as $row)
                foreach ($row as $value)
                    $this->statement->bindValue($count++, $value);

            return $this->statement->execute();
        } catch (\Exception $error) {
            if (!$silent)
                throw $error;

            return false;
        }
    }

    /**
     * Inserts the data into specified table.
     * 
     * @param  array  $data
     * @param  bool  $exec
     * @return bool|\Illuminate\Database\Query\Builder
     * @throws \InvalidArgumentException
     */
    public function insert(array $data, bool $exec = true): bool|self
    {
        // Data should be an associative array of keys as column names and 
        // values are value for corresponding column or a numeric array
        // containing associative arrays each representing a record.
        if (!$data)
            throw new InvalidArgumentException('Data must be an associative array of column names and values or a numeric array of multiple associative arrays!');

        // Table name must be specified!
        if (!$this->from)
            throw new InvalidArgumentException('No table name specified before inserting data!');

        $bindings = [];

        // It is an associative array of single row
        if (is_assoc($data)) {
            $bindings = array_values($data);
            $columns = array_keys($data);
            $placeholders = sprintf('(%s)', implode(', ', array_fill(0, count($bindings), '?')));
        }

        // A numeric array of associative arrays where key is column name and
        // values are the values for each column
        else {
            $columns = array_keys(reset($data));

            // Process each value and discover missing columns
            foreach ($data as $row) {

                // Check if a new column name is encountered then add it to
                // the end so client defaults it to NULL and previous records
                // are not affected
                foreach (array_keys($row) as $key)
                    if (is_string($key) && !in_array($key, $columns))
                        $columns[] = $key;

                $values = [];

                // Values might not be in order so we order them by column name
                // occurrence so each row has values in same order
                foreach ($columns as $column)
                    $values[] = array_key_exists($column, $row) ? $row[$column] : null;

                $bindings[] = $values;
            }

            $total_cols = count($columns);

            // Populate values for discoverd columns
            for ($i = 0; $i < count($bindings); $i++) {
                $cols_count = count($bindings[$i]);

                if ($cols_count >= $total_cols)
                    continue;

                $extras = array_fill($cols_count, $total_cols - $cols_count, null);
                $bindings[$i] = array_merge($bindings[$i], $extras);
            }

            $placeholders = sprintf('(%s)', implode(', ', array_fill(0, $total_cols, '?')));
            $placeholders = implode(', ', array_fill(0, count($data), $placeholders));
        }

        $columns = implode(', ', $columns);
        $query = sprintf('INSERT INTO `%s` (%s) VALUES %s', $this->from, $columns, $placeholders);

        $query = $this->query($query, $bindings);

        return $exec ? $query->run() : $query;
    }

    /**
     * Adds columns for selection in select clause.
     * 
     * @param  array  $cols
     * @return \Illuminate\Database\Query\Builder
     */
    public function select($columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Merges additional selective columns in select clause.
     * 
     * @param  array  $cols
     * @return \Illuminate\Database\Query\Builder
     */
    public function addSelect($columns = ['*']): self
    {
        $this->columns = array_merge(
            $this->columns,
            is_array($columns) ? $columns : func_get_args()
        );

        return $this;
    }

    /**
     * Builds the select query if no query was specified.
     * 
     * @return string
     */
    protected function getBaseSelectQuery()
    {
        return sprintf('SELECT %s FROM %s', implode(', ', $this->buildColumns()), $this->from);
    }

    /**
     * Wraps the value in appropriate symbols.
     * 
     * @param  mixed  $value
     * @return mixed
     */
    protected function wrap(mixed $value): mixed
    {
        // Replace nullish value with SQL's `NULL` keyword
        if (!$value)
            return 'NULL';

        // Wrap strings inside single quotations
        if (is_string($value))
            return sprintf("'%s'", str_replace("'", "\'", $value));

        // Convert booleans to 1s and 0s
        if (is_bool($value))
            return $value ? '1' : '0';

        return $value;
    }

    /**
     * Returns the selected columns.
     * 
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Generates safe column names for injecting into queries.
     * 
     * @return array<string>
     */
    protected function buildColumns(): array
    {
        $columns = [];

        foreach ($this->columns as $column => $as) {
            // If column name is the key and raw expression has been passed as
            // the value then we will combine both to form an alias or a
            // sub-query expression
            if (is_string($column) && !is_numeric($column))
                $columns[] = sprintf('%s %s', $column, $as);

            // Only column name was specified
            else $columns[] = $as;
        }


        return $columns;
    }
}
