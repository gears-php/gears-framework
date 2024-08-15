<?php

/**
 * @author denis.krasilnikov@gears.com
 */
declare(strict_types=1);

namespace Gears\Db;

use ArrayAccess;
use Gears\Db\Adapter\Mysql;
use Gears\Db\Adapter\Sqlite;
use PDO;
use PDOException;
use PDOStatement;
use Gears\Db\Query\WhereAbstract;
use Gears\Db\Query\WhereAnd;
use RuntimeException;

/**
 * Abstract db adapter is a PDO wrapper bringing more handy and laconic functionality over the last one
 * @package Gears\Db\Db
 */
abstract class Db implements ArrayAccess
{
    /**
     * Active database connection
     */
    protected PDO $connection;

    /**
     * Active PDO query result statement
     */
    protected PDOStatement $statement;

    /**
     * Hold the very last query being executed with adapter
     */
    protected string|Query $lastQuery;

    /**
     * Instantiate new db adapter connection
     * @param array $config Connection properties
     * @param array $options Additional connection options
     * @return static
     */
    public static function connect(array $config, array $options = []): static
    {
        return new ([
            'mysql' => Mysql::class,
            'sqlite' => Sqlite::class,
        ][strtolower($config['driver'])])(
            $config, $options
        );
    }

    /**
     * Create PDO database connection using the given connection parameters
     *
     * @param array $config Connection properties
     * @param array $options Additional connection options
     */
    public function __construct(array $config, array $options = [])
    {
        $this->connection = $this->createConnection($config, $options);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepare the given sql query
     */
    public function prepare(string|Query $query): self
    {
        $this->statement = $this->connection->prepare(($this->lastQuery = $query) . '');

        return $this;
    }

    /**
     * Execute query previously prepared with {@see prepare()}
     *
     * @throws RuntimeException
     */
    public function execute(array $params): self
    {
        try {
            $this->statement->execute($params);
        } catch (PDOException $e) {
            throw new RuntimeException('Error executing the query: ' . $this->lastQuery, 0, $e);
        }

        return $this;
    }

    /**
     * Prepare and execute the given query
     */
    public function query(string|Query $query, array $params = []): self
    {
        $this->prepare($query)->execute($params);

        return $this;
    }

    public function getStatement(): PDOStatement
    {
        return $this->statement;
    }

    /**
     * Fetch multiple rows
     */
    public function fetchAll(int $fetchStyle = PDO::FETCH_ASSOC): array
    {
        return $this->statement->fetchAll($fetchStyle);
    }

    /**
     * Fetch multiple rows grouped by the specific column
     */
    public function fetchAssoc(int $fetchStyle = PDO::FETCH_ASSOC): array
    {
        return $this->statement->fetchAll(PDO::FETCH_GROUP | $fetchStyle);
    }

    /**
     * Fetch a first result row
     */
    public function fetchRow(int $fetchStyle = PDO::FETCH_ASSOC): array|bool
    {
        return $this->statement->fetch($fetchStyle);
    }

    /**
     * Fetch a single result set column
     */
    public function fetchCol(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Fetch array with the first query result column used for keys and second one - for the values
     */
    public function fetchPairs(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Fetch a single cell value from a first result row
     * @return mixed Table cell value or false otherwise
     */
    public function fetchOne(): mixed
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Escape given value making it safe to be used in SQL query
     */
    public function escape(mixed $value): mixed
    {
        if ($value === null) {
            return 'null';
        }

        return is_string($value) ? $this->connection->quote($value) : $value;
    }

    /**
     * Escape the given identifier (e.g. field name, table name)
     */
    abstract public function escapeIdentifier(string $identifier);

    /**
     * Get driver-specific SQL clause for limiting selected data set results.
     */
    abstract public function getLimitClause(int $count, int $offset): string;

    /**
     * Db driver specific method which allows to get the total row count of the latest performed select query
     */
    public function getLastRowCount(): int
    {
        throw new RuntimeException(__METHOD__ . ' is not supported by current db driver');
    }

    /**
     * Get id of the last inserted row
     */
    public function getLastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Insert multiple rows. Returns number of inserted rows or false
     */
    public function insert(string $tableName, array $rows): bool|int
    {
        // build fields for sql
        ksort($rows[0]);
        $fields = array_map(
            function ($field) {
                return $this->escapeIdentifier($field);
            },
            array_keys($rows[0])
        );

        // build values for sql
        foreach ($rows as &$row) {
            ksort($row); // make sure all rows follow same field values order
            $row = sprintf(
                '(%s)',
                implode(
                    ',',
                    array_map(
                        function ($value) {
                            return $this->escape($value);
                        },
                        $row
                    )
                )
            );
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->escapeIdentifier($tableName),
            implode(',', $fields),
            implode(',', $rows)
        );

        return $this->connection->exec($sql);
    }

    /**
     * Update table record(s) matched by the given where clause. Returns number of affected rows or false
     */
    public function update(string $tableName, array $data, array|WhereAbstract $where): bool|int
    {
        if (is_array($where)) {
            $where = (new WhereAnd($this))->fromArray($where);
        }

        array_walk(
            $data,
            function (&$value, $field) {
                $value = $this->escapeIdentifier($field) . '=' . (is_bool($value) ? (int)$value : $this->escape($value));
            }
        );

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->escapeIdentifier($tableName),
            implode(',', $data),
            $where->toString()
        );

        return $this->connection->exec($sql);
    }

    /**
     * Delete table record(s) matched by the given where clause
     */
    public function delete(string $tableName, array|WhereAbstract $where): bool
    {
        if (is_array($where)) {
            $where = (new WhereAnd($this))->fromArray($where);
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->escapeIdentifier($tableName), $where->toString());

        return (bool)$this->connection->exec($sql);
    }

    /**
     * Return Dataset instance for table records manipulation
     */
    public function get(string $tableName): Dataset
    {
        return new Dataset($tableName, $this);
    }

    public function offsetExists(mixed $offset): bool
    {
        return true;
    }

    /**
     * Return dataset by a given table name
     */
    public function offsetGet(mixed $offset): ?Dataset
    {
        if (is_string($offset)) {
            return $this->get($offset);
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }

    /**
     * Create new db connection based on given connection config parameters and additional options
     */
    abstract protected function createConnection(array $config, array $options = []): PDO;
}
