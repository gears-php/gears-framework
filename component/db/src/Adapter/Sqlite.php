<?php

declare(strict_types=1);

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
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $identifier): string
    {
        return '"' . $identifier . '"';
    }

    /**
     * Override parent since SQLite does not support a single statement multiple row insert
     * {@inheritdoc}
     */
    public function insert(string $tableName, array $rows): bool|int
    {
        $count = 0;

        foreach ($rows as $row) {
            $count += parent::insert($tableName, [$row]);
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnection(array $config, array $options = []): PDO
    {
        return new PDO("{$config['driver']}:{$config['file']}");
    }

    /**
     * {@inheritdoc}
     */
    public function getLimitClause(int $count, int $offset): string
    {
        return sprintf('LIMIT %d OFFSET %d', $count, $offset);
    }
}
