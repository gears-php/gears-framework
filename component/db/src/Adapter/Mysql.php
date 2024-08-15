<?php
/**
 * @author denis.krasilnikov@gears.com
 */

namespace Gears\Db\Adapter;

use Gears\Db\Db;

/**
 * Implements database adapter functionality specific to MySQL db storage
 * @package Gears\Db\Db
 */
final class Mysql extends Db
{
    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $identifier): string
    {
        return "`" . str_replace('`', '``', $identifier) . "`";
    }

    /**
     * {@inheritdoc}
     */
    public function getLastRowCount(): int
    {
        return intval($this->connection->query('SELECT FOUND_ROWS()')->fetchColumn());
    }

    /**
     * {@inheritdoc}
     */
    public function getLimitClause(int $count, int $offset): string
    {
        return sprintf('LIMIT %d, %d', $offset, $count);
    }

    /** {@inheritdoc} */
    protected function createConnection(array $config, array $options = []): \PDO
    {
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['dbname']}";

        return new \PDO($dsn, $config['user'], $config['pass'], $options);
    }
}