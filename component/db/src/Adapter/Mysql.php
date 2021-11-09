<?php
/**
 * @author denis.krasilnikov@gears.com
 */

namespace Gears\Db\Adapter;

/**
 * Implements database adapter functionality specific to MySQL db storage
 * @package Gears\Db\Adapter
 */
class Mysql extends AdapterAbstract
{
    /**
     * {@inheritdoc}
     */
    protected array $patterns = [
        'create_table' => [
            ' pk ' => ' INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ',
            ' string ' => ' TEXT ',
            ' float ' => ' REAL ',
        ],
    ];

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
}