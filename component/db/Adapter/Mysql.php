<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Framework\Db\Adapter;

/**
 * Implements database adapter functionality specific to MySQL db storage
 * @package Gears\Framework\Db\Adapter
 */
class Mysql extends AdapterAbstract
{
    protected $driver = 'mysql';

    public function escapeIdentifier($identifier)
    {
        return "`" . str_replace('`', '``', $identifier) . "`";
    }

    public function getLastRowCount()
    {
        return intval($this->connection->query('SELECT FOUND_ROWS()')->fetchColumn());
    }

    protected function getPlaceholderIgnoreRegex()
    {
        return '
            "   (?> [^"\\\\]+|\\\\"|\\\\)*    "   |
            \'  (?> [^\'\\\\]+|\\\\\'|\\\\)* \'   |
            `   (?> [^`]+ | ``)*              `   |   # backticks
            /\* .*?                          \*/      # comments
        ';
    }
}