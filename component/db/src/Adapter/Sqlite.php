<?php

namespace Gears\Db\Adapter;

use PDO;

/**
 * SQLite db adapter
 * @package Gears\Db\Adapter
 */
class Sqlite extends AdapterAbstract
{
    /**
     * {@inheritdoc}
     */
    protected array $patterns = [
        'create_table' => [
            ' pk ' => ' INTEGER PRIMARY KEY NOT NULL ',
            ' string ' => ' VARCHAR ',
            ' float ' => ' REAL ',
            ' bool ' => ' BOOL ',
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
     * {@inheritdoc}
     */
    public function insert($tableName, $rows)
    {
        foreach ($rows as $row) {
            parent::insert($tableName, [$row]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnection(array $config, $options = [])
    {
        return new PDO("{$config['driver']}:{$config['file']}");
    }
}
