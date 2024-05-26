<?php

namespace Gears\Db;

use Gears\Db\Query\WhereAnd;

/**
 * Class for rows manipulation over particular table
 * @package   Gears\Db
 */
class Dataset
{
    /**
     * Table name
     * @var string
     */
    protected $tableName;

    /**
     * Table primary key
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Db adapter instance holder
     * @var Adapter\AdapterAbstract
     */
    protected $db;

    /**
     * Query instance holding current dataset sql query
     * @var Query
     */
    protected $query;

    /**
     * Instantiate data set
     * @param string $tableName
     * @param Adapter\AdapterAbstract $db
     */
    public function __construct($tableName, Adapter\AdapterAbstract $db)
    {
        $this->tableName = $tableName;
        $this->query = (new Query($db))
            ->from($tableName)
            ->where(new WhereAnd($db));
        $this->db = $db;
    }

    /**
     * Insert one or more rows into table. Each row is passed as a function argument
     * @param \array[] ...$rows
     * @return int Number of inserted rows
     */
    public function insert(array ...$rows): int
    {
        return (int)$this->db->insert($this->tableName, $rows);
    }

    /**
     * Update db table rows matching given `where` criteria with new data
     * @param array $data New data to be put
     * @param array $where Where condition to match the rows for update
     * @return int Number of updated rows
     */
    public function update(array $data, array $where = []): int
    {
        return (int)$this->db->update($this->tableName, $data, $where);
    }

    /**
     * Delete db table rows which do match given `where` criteria
     * @param array $where
     * @return int Number of deleted rows
     */
    public function delete(array $where = []): int
    {
        return (int)$this->db->delete($this->tableName, $where);
    }

    /**
     * Return data set record count
     * @return int
     */
    public function count(): int
    {
        // todo write logic
    }

    /**
     * Select some specific field(s)
     * @param string|array $field Field name
     * @param string $alias (optional) Field alias
     * @return $this
     */
    public function select($field, $alias = null)
    {
        $this->query->select($field, $alias, $this->tableName);

        return $this;
    }

    /**
     * Filter dataset by field=value equality condition
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function filter($field, $value)
    {
        $this->query->getWhere()->eq($field, $value);

        return $this;
    }

    /**
     * Add where condition string with optional placeholder parameters
     * OR add where condition object
     * @return $this
     */
    public function where()
    {
        call_user_func_array([$this->query->getWhere(), 'add'], func_get_args());

        return $this;
    }

    /**
     * Alias to the {@see fetchAll()}
     * @return array
     */
    public function all(): array
    {
        return $this->fetchAll();
    }

    /**
     * Alias to the {@see fetchOne()}
     * @return mixed
     */
    public function one()
    {
        return $this->fetchOne();
    }

    /**
     * Alias to the {@see fetchRow()}
     * @return array
     */
    public function row(): array
    {
        return $this->fetchRow();
    }

    /**
     * Alias to the {@see fetchCol()}
     * @return array
     */
    public function col(): array
    {
        return $this->fetchCol();
    }

    /**
     * Alias to the {@see fetchPairs()}
     * @return array
     */
    public function pairs(): array
    {
        return $this->fetchPairs();
    }

    /**
     * Fetch all rows by executing current query
     * @return array Fetched records
     */
    public function fetchAll(): array
    {
        return $this->query->exec()->fetchAll();
    }

    /**
     * Fetch a single cell value from a first dataset row
     * @return mixed Table cell value or false otherwise
     */
    public function fetchOne(): array
    {
        return $this->query->exec()->fetchOne();
    }

    /**
     * Fetch a first dataset row
     * @return array
     */
    public function fetchRow(): array
    {
        return $this->query->exec()->fetchRow();
    }

    /**
     * Fetch single column data
     * @return array
     */
    public function fetchCol(): array
    {
        return $this->query->exec()->fetchCol();
    }

    /**
     * Fetch array with the first dataset column used for keys and second one - for the values
     * @return array
     */
    public function fetchPairs(): array
    {
        return $this->query->exec()->fetchPairs();
    }

    /**
     * Find and return the row(s) by the given filters
     * @param array $where
     * @return array
     */
    public function find(array $where): array
    {
        $fields = $values = [];

        foreach ($where as $field => $value) {
            $fields[] = sprintf('%s = ?', $this->db->escapeIdentifier($field));
            $values[] = $value;
        }

        $sql = sprintf('SELECT * FROM %s WHERE %s',
            $this->db->escapeIdentifier($this->tableName),
            implode(' AND ', $fields)
        );

        return $this->db->query($sql, $values)->fetchAll();
    }

    /**
     * Order the dataset selection by specified column(s). Default sorting is ASC
     */
    public function order(?string $field, string $tableName, string $sort = Query::ASC): static
    {
        $this->query->order($field, $tableName, $sort);

        return $this;
    }
}
