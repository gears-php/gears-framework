<?php

/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use PDO;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Db\Query\WhereAnd;
use Gears\Db\Query;
use RuntimeException;

/**
 * Allows to build a query which will fetch data into Active Record class object(s)
 * @package Gears\Db
 */
class ActiveQuery extends Query
{
    /**
     * {@inheritdoc}
     */
    public function __construct(protected AdapterAbstract $db, private ActiveRecord $activeRecord)
    {
        parent::__construct($db);
    }

    /**
     * Get query subject active record
     */
    public function getActiveRecord(): ActiveRecord
    {
        return $this->activeRecord;
    }

    /**
     * Query ActiveRecord list
     * @return ActiveRecord[]
     */
    public function fetchAll($mode = PDO::FETCH_CLASS): array
    {
        return $this->exec()
            ->getStatement()
            ->fetchAll(
                $mode,
                get_class($this->activeRecord),
                [$this->activeRecord->getManager()]
            );
    }

    /**
     * Get ActiveNode`s tree list where all child nodes put under their parents.
     */
    public function fetchTree(): array
    {
        if (!$this->activeRecord instanceof ActiveNode) {
            throw new RuntimeException(sprintf('Class %s must implement ActiveNode for tree operations', get_class($this->activeRecord)));
        }

        array_unshift($this->select, $this->db->escapeIdentifier($this->activeRecord->getPrimaryKey()));
        $records = array_map(fn($rec) => $rec[0], $this->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_GROUP));

        if (!$records) {
            return [];
        }

        /** @var ActiveNode[] $refs */
        $refs = $moved = [];
        $record = current($records);

        // build tree from a flat record set
        do {
            $parentId = $record->parent_id;

            if (isset($refs[$parentId])) { // put node as a child to a parent
                $refs[$parentId]->addChild($record);
                $childrenCount = count($refs[$parentId]->getChildren());
                $refs[$record->id] = &$refs[$parentId]->getChildren()[$childrenCount - 1];
                $moved[] = $record->id;
            } else { // top level node
                $refs[$record->id] = &$records[key($records)];
            }
        } while ($record = next($records));

        // remove all moved child nodes from top level
        $records = array_diff_key($records, array_flip($moved));

        return array_values($records);
    }

    /**
     * Query a single ActiveRecord
     */
    public function fetchOne(): ?ActiveRecord
    {
        return $this->exec()
            ->getStatement()
            ->fetchObject(get_class($this->activeRecord), [$this->activeRecord->getManager()]) ?: null;
    }

    public function fetchById(string $id): ?ActiveRecord
    {
        $this->getWhere()->eq($this->activeRecord->getPrimaryKey(), $id);

        return $this->fetchOne();
    }

    /**
     * Build default query for fetching active record(s) using metadata information
     */
    public function build(): ActiveQuery
    {
        $meta = $this->activeRecord->getMetadata();

        return $this->select($meta['fields'], null, $meta['tableName'])
            ->from($meta['tableName'])
            ->where(new WhereAnd($this->db))
            ->order($meta['sortBy']);
    }
}
