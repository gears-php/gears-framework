<?php
/**
 * @author deniskrasilnikov86@gmail.com
 *
 * Functionality for custom query placeholders support and query conditional blocks processing is a
 * refactored and much simplified version of same functionality taken from DbSimple library
 * (https://github.com/DmitryKoterov/DbSimple)
 */
namespace Gears\Framework\Db\Adapter;

/**
 * Abstract db adapter is a PDO wrapper bringing more handy and laconic functionality over the last one
 * @package Gears\Framework\Db\Adapter
 */
abstract class AdapterAbstract
{
    const DB_SKIP = 'DB_SKIP';

    /**
     * Concrete db driver name should be defined inside concrete db-specific adapter class
     * @var string
     */
    protected $driver;

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
     * @var string
     */
    private $identifierPrefix = '';

    /**
     * Stores query placeholder parameters during query expanding (processing)
     * @var array
     */
    private $placeholderArgs = [];

    /**
     * Remembers if there was no placeholder param value found
     * @var boolean
     */
    private $placeholderNoValueFound = false;

    /**
     * Create database connection using the given connection parameters
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $dbname
     */
    public function __construct($host, $user, $pass, $dbname)
    {
        $this->connection = new \PDO("$this->driver:host=$host;dbname=$dbname", $user, $pass);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepare the given query for execution
     * @param string $query
     * @return $this
     */
    public function prepare($query)
    {
        $this->statement = $this->connection->prepare($query);
        return $this;
    }

    /**
     * Execute latest prepared query with given params
     * @param array $params
     * @return $this;
     */
    public function execute(array $params = array())
    {
        $this->statement->execute($params);
        // by default each fetched row will be return as an associative array
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $this;
    }

    /**
     * Process and execute query
     * @return $this
     */
    public function query()
    {
        try {
            $args = func_get_args();
            // first arg must be the query string
            $query = array_shift($args);
            // next args should be query placeholder params
            $this->expandPlaceholders($query, $args);
            // first parameter should be a query
            $this->prepare($query);
            // execute prepared SQL statement with given parameter values (if any)
            $this->execute($args);
        } catch (\PDOException $e) {
            // todo: exception should include full (prepared) query string
            throw $e;
        }
        return $this;
    }

    /**
     * Select multiple rows
     * @return array Array of row arrays
     * @todo drop ARRAY_KEY support and use fetchAll() instead (with PDO::FETCH_COLUMN | PDO::FETCH_GROUP ?)
     */
    public function fetchRows()
    {
        $rows = [];
        while ($row = $this->statement->fetch()) {
            if (isset($row['ARRAY_KEY'])) {
                $rows[$arrKey = $row['ARRAY_KEY']] = $row;
                unset($rows[$arrKey]['ARRAY_KEY']);
            } else {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Return a single cell value from a row
     * @return string Table cell value or false otherwise
     */
    public function fetchOne()
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Select a single db row. Will always return the very first result set row
     * @return array
     */
    public function fetchRow()
    {
        return $this->statement->fetch();
    }

    /**
     * Select a single result set column
     * @return array
     */
    public function fetchCol()
    {
        return $this->statement->fetchAll(\PDO::FETCH_COLUMN);
    }


    /**
     * Escape given value making it safe to be used in SQL query
     * @param $value
     * @return string SQL-safe value
     */
    public function escape($value)
    {
        return $this->connection->quote($value);
    }

    /**
     * Db driver specific method which allows to get the total row count of the latest performed select query
     * @return int
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
     * Escape the given identifier (e.g. field name, table name)
     * @param $identifier
     * @return string
     */
    abstract public function escapeIdentifier($identifier);

    /**
     * @return string
     * Return regular expression which matches ignored query parts.
     * This is needed to skip placeholder replacement inside comments, constants etc.
     */
    abstract protected function getPlaceholderIgnoreRegex();

    /**
     * Process query by replacing non-native custom placeholders with their real argument values counterparts
     * @param string $query Initial query
     * @param array $queryParams Array of query placeholder parameters
     */
    private function expandPlaceholders(&$query, &$queryParams)
    {
        $this->placeholderArgs = $queryParams;
        $this->placeholderNoValueFound = false;
        $query = $this->expandPlaceholdersFlow($query);
        $queryParams = $this->placeholderArgs;
    }

    /**
     * Do custom query placeholders processing. Imply that all interval
     * variables (_placeholder_*) already prepared. May be called recurrently
     * @return string
     */
    private function expandPlaceholdersFlow($query)
    {
        $regex = '{
            (?>
                # Ignored chunks
                (?>
                    # Comment
                    -- [^\r\n]*
                )
                  |
                (?>
                    # DB-specifics
                    ' . trim($this->getPlaceholderIgnoreRegex()) . '
                )
            )
              |
            (?>
                # Optional blocks
                \{
                    # Use "+" here, not "*"! Else nested blocks are not processed well.
                    ( (?> (?>[^{}]+)  |  (?R) )* ) #1
                \}
            )
              |
            (?>
                # Placeholder
                (\?) ( [_dsafn\#]? ) #2 #3
            )
        }sx';
        return preg_replace_callback(
            $regex,
            array($this, 'expandPlaceholdersCallback'),
            $query
        );
    }

    /**
     * Internal function to replace placeholders (see preg_replace_callback).
     * @return string
     */
    private function expandPlaceholdersCallback($matched)
    {
        // Placeholder.
        if (!empty($matched[2])) {
            $type = $matched[3];

            if ($type == '_') {
                // identifier prefix
                return $this->identifierPrefix;
            }

            // value-based placeholder
            if (!$this->placeholderArgs) return 'DB_ERROR_NO_VALUE';
            $value = array_shift($this->placeholderArgs);

            // Skip this value?
            if ($value === self::DB_SKIP) {
                $this->placeholderNoValueFound = true;
                return '';
            }

            // First process guaranteed non-native placeholders
            switch ($type) {
                case 'a':
                    if (!$value) $this->placeholderNoValueFound = true;
                    if (!is_array($value)) return 'DB_ERROR_VALUE_NOT_ARRAY';
                    $parts = array();
                    foreach ($value as $k => $v) {
                        $v = $v === null ? 'NULL' : $this->escape($v);
                        if (!is_int($k)) {
                            $k = $this->escapeIdentifier($k);
                            $parts[] = "$k=$v";
                        } else {
                            $parts[] = $v;
                        }
                    }
                    return join(', ', $parts);
                case "#":
                    // Identifier
                    if (!is_array($value)) return $this->escapeIdentifier($value);
                    $parts = array();
                    foreach ($value as $table => $identifier) {
                        if (!is_string($identifier)) return 'DB_ERROR_ARRAY_VALUE_NOT_STRING';
                        $parts[] = (!is_int($table) ? $this->escapeIdentifier($table) . '.' : '')
                            . $this->escapeIdentifier($identifier, true);
                    }
                    return join(', ', $parts);
                case 'n':
                    // NULL-based placeholder
                    return empty($value) ? 'NULL' : intval($value);
            }

            // In non-native mode arguments are quoted
            if ($value === null) return 'NULL';
            switch ($type) {
                case '':
                    if (!is_scalar($value)) return 'DBSIMPLE_ERROR_VALUE_NOT_SCALAR';
                    return $this->escape($value);
                case 'd':
                    return intval($value);
                case 'f':
                    return str_replace(',', '.', floatval($value));
            }

            // By default doing native escape
            return $this->escape($value);
        }

        // Optional block
        if (isset($matched[1]) && strlen($block = $matched[1])) {
            $prev = $this->placeholderNoValueFound;
            $block = $this->expandPlaceholdersFlow($block);
            $block = $this->placeholderNoValueFound ? '' : ' ' . $block . ' ';
            $this->placeholderNoValueFound = $prev; // recurrent-safe
            return $block;
        }

        // Default: skipped part of the string
        return $matched[0];
    }
}