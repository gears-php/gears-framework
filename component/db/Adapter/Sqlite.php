<?php

namespace Gears\Db\Adapter;

/**
 * SQLite db adapter
 * @package Gears\Db\Adapter
 */
class Sqlite extends AdapterAbstract
{
    /**
     * {@inheritdoc}
     */
    protected $patterns = [
        'create_table' => [
            ' pk ' => ' INTEGER PRIMARY KEY ',
            ' string ' => ' TEXT ',
            ' float ' => ' REAL '
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($name)
    {
        return '"' . $name . '"';
    }

    /**
     * Override parent since SQLite does not support a single statement multiple row insert
     * @param string $tableName
     * @param array $rows
     */
    public function insert($tableName, $rows)
    {
        foreach ($rows as $row) {
            parent::insert($tableName, [$row]);
        }
    }
}