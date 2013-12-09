<?php
/**
 * @author deniskrasilnikov86@gmail.com
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
    protected $patterns = [
        'create_table' => [
            ' pk ' => ' INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ',
            ' string ' => ' TEXT ',
            ' float ' => ' REAL '
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($name)
    {
        return "`" . str_replace('`', '``', $name) . "`";
    }

    /**
     * {@inheritdoc}
     */
    public function getLastRowCount()
    {
        return intval($this->connection->query('SELECT FOUND_ROWS()')->fetchColumn());
    }
}