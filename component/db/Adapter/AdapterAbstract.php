<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Db\Adapter;

use Gears\Db\Query;
use Gears\Db\Query\WhereAbstract;
use Gears\Db\Query\WhereAnd;
use Gears\Db\Dataset;

/**
 * Abstract db adapter is a PDO wrapper bringing more handy and laconic functionality over the last one
 * @package Gears\Db\Adapter
 */
abstract class AdapterAbstract implements \ArrayAccess
{
    /**
     * Search/replace patterns collection for various SQL statements building.
     * Defined in each specific db adapter
     * @var array
     */
    protected $patterns = array();

    /**
     * Active database connection
     * @var \PDO
     */
    protected $connection = null;

    /**
     * Active PDO query result statement
     * @var \PDOStatement
     */
    private $statement = null;

    /**
     * Create PDO database connection using the given connection parameters
     * @param array $config Connection properties
     * @param array $options Additional connection options
     */
    public function __construct(array $config, array $options = array())
    {
        $dsn = $this->getConnectionString($config);
        $this->connection = new \PDO($dsn, $config['user'], $config['pass'], $options);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepare and execute the given query
     * @param string|Query $query
     * @param array $params
     * @return $this
     */
    public function query($query, array $params = array())
    {
        $this->statement = $this->connection->prepare($query . '');
        $this->statement->execute($params);
        // by default each fetched row will be return as an associative array
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $this;
    }

    /**
     * Fetch multiple rows
     * @return array Array of rows
     */
    public function fetchAll()
    {
        return $this->statement->fetchAll();
    }

    /**
     * Fetch multiple rows using the given field as an array key for each fetched row
     * @return array Array of rows
     * @todo use fetchAll() instead (with PDO::FETCH_COLUMN | PDO::FETCH_GROUP ?)
     */
    public function fetchAssoc($key = 'id')
    {
        $rows = [];
        while ($row = $this->statement->fetch()) {
            if (isset($row[$key])) {
                $rows[$row[$key]] = $row;
            } else {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Fetch a single cell value from a first result row
     * @return string Table cell value or false otherwise
     */
    public function fetchOne()
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Fetch a first result row
     * @return array
     */
    public function fetchRow()
    {
        return $this->statement->fetch();
    }

    /**
     * Fetch a single result set column
     * @return array
     */
    public function fetchCol()
    {
        return $this->statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Fetch array with the first query result column used for keys and second one - for the values
     * @return array
     */
    public function fetchPairs()
    {
        return $this->statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Escape given value making it safe to be used in SQL query
     * @param mixed $value Scalar
     * @return string Safe value
     */
    public function escape($value)
    {
        return is_scalar($value) ? $this->connection->quote($value) : 'DB_ERROR_VALUE_NOT_SCALAR';
    }

    /**
     * Escape the given identifier (e.g. field name, table name)
     * @param $identifier
     * @return string
     */
    abstract public function escapeIdentifier($identifier);

    /**
     * Db driver specific method which allows to get the total row count of the latest performed select query
     * @return integer
     */
    public function getLastRowCount()
    {
        throw new \Exception(__METHOD__ . ' is not supported by current db driver');
    }

    /**
     * Get id of the last inserted row
     * @return string
     */
    public function getLastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Create a new table with a given name and field definitions
     * @param string $tableName
     * @param array $fields
     */
    public function create($tableName, array $fields)
    {
        $patterns = $this->patterns['create_table'];
        $fields = array_map(function ($field) use ($patterns) {
            return trim(str_replace(array_keys($patterns), array_values($patterns), $field . ' '));
        }, $fields);
        $sql = sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $this->escapeIdentifier($tableName), implode(",", $fields));
        $this->connection->exec($sql);
    }

    /**
     * Drop the table
     * @param string $tableName
     */
    public function drop($tableName)
    {
        $sql = sprintf('DROP TABLE IF EXISTS %s', $this->escapeIdentifier($tableName));
        $this->connection->exec($sql);
    }

    /**
     * Insert multiple table rows
     * @param string $tableName
     * @param array $rows Collection of row hashes
     * @return integer|boolean Number of inserted rows or false
     */
    public function insert($tableName, $rows)
    {
        // build fields sql
        ksort($rows[0]);
        $fields = array_map(function ($field) {
            return $this->escapeIdentifier($field);
        }, array_keys($rows[0]));

        // build values sql
        foreach ($rows as &$row) {
            ksort($row); // make sure all rows follow same field values order
            $row = sprintf('(%s)', implode(',', array_map(function ($value) {
                return $this->escape($value);
            }, $row)));
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES %s',
            $this->escapeIdentifier($tableName),
            implode(',', $fields),
            implode(',', $rows)
        );

        return $this->connection->exec($sql);
    }

    /**
     * Update table record(s) matched by the given where clause
     * @param string $tableName
     * @param array $data New record data
     * @param array|WhereAbstract $where
     * @return integer|boolean Number of affected rows or false
     */
    public function update($tableName, $data, $where)
    {
        if (is_array($where)) {
            $where = (new WhereAnd($this))->fromArray($where);
        }

        array_walk($data, function (&$value, $field) {
            $value = $this->escapeIdentifier($field) . '=' . $this->escape($value);
        });

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $tableName, implode(',', $data), $where->toString());
        return $this->connection->exec($sql);
    }

    /**
     * Delete table record(s) matched by the given where clause
     * @param string $tableName
     * @param array|WhereAbstract $where
     * @return integer|boolean Number of affected rows or false
     */
    public function delete($tableName, $where)
    {
        if (is_array($where)) {
            $where = (new WhereAnd($this))->fromArray($where);
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', $tableName, $where->toString());
        return $this->connection->exec($sql);
    }

    /**
     * Return Dataset instance for table records manipulation
     * @param string $tableName
     * @return Dataset
     */
    public function get($tableName)
    {
        return new Dataset($tableName, $this);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * Return dataset by a given table name
     * @param mixed $offset
     * @return Dataset|null
     */
    public function offsetGet($offset)
    {
        if (is_string($offset)) {
            return $this->get($offset);
        }
        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * Build and return db connection string based on given connection parameters
     * @param array $config
     * @return string
     */
    protected function getConnectionString(array $config)
    {
        return "{$config['driver']}:host={$config['host']};dbname={$config['dbname']}";
    }
}