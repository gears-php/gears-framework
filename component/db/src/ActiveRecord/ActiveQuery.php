<?php

/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;
use PDO;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Db\Query\WhereAnd;
use Gears\Db\Query;

/**
 * Querying Active Record(s) from db
 * @package Gears\Db
 */
class ActiveQuery extends Query
{
    use MetadataAware;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        protected AdapterAbstract $db,
        private readonly ActiveManager $manager,
        private readonly Storage $metadata
    ) {
        parent::__construct($db);
    }

    /**
     * Get query subject active record
     */
    public function getMetadata(): Storage
    {
        return $this->metadata;
    }

    public function fetchRecords(): array
    {
        return is_subclass_of($this->getClassName(), ActiveNode::class) ? $this->fetchTree() : $this->fetchAll();
    }

    /**
     * Query a single ActiveRecord
     */
    public function fetchOne(): ?ActiveRecord
    {
        $records = $this->exec()
            ->getStatement()
            ->fetchAll(PDO::FETCH_FUNC, [$this, 'createRecord']);
        return count($records) == 1 ? $records[0] : null;
    }

    public function fetchById(string $id): ?ActiveRecord
    {
        $this->getWhere()->eq($this->getPrimaryKey(), $id);

        return $this->fetchOne();
    }

    /**
     * Build default query for fetching active record(s) using metadata information
     */
    public function build(): ActiveQuery
    {
        return $this->select($this->getFields(), null, $this->getTableName())
            ->from($this->getTableName())
            ->where(new WhereAnd($this->db))
            ->order($this->metadata['sortBy'] ?? [], $this->getTableName());
    }

    public function createRecord(...$dbData): ActiveRecord
    {
        $className = $this->getClassName();
        /** @var ActiveRecord $record */
        $record = new $className($this->manager, $this->metadata);

        return $record->init($dbData);
    }

    /**
     * Query ActiveRecord plain list
     * @return ActiveRecord[]
     */
    private function fetchAll(): array
    {
        return $this->exec()->getStatement()->fetchAll(PDO::FETCH_FUNC, [$this, 'createRecord']);
    }

    /**
     * Get ActiveNode tree structure where all child nodes put under their parents.
     */
    private function fetchTree(): array
    {
        $records = $this->fetchAll();
        $records = array_combine(array_column($records, $primaryKey = $this->getPrimaryKey()), $records);

        if (!$records) {
            return [];
        }

        /** @var ActiveNode[] $refs */
        $refs = $moved = [];
        $record = current($records);

        // build tree from a flat record set
        do {
            /** @var ActiveNode $record */
            $parentId = $record->{$this->getParentKey()};

            if (isset($refs[$parentId])) { // put node as a child to a parent
                $refs[$parentId]->addChild($record);
                $childrenCount = count($refs[$parentId]->getChildren());
                $refs[$record->$primaryKey] = &$refs[$parentId]->getChildren()[$childrenCount - 1];
                $moved[] = $record->$primaryKey;
            } else { // top level node
                $refs[$record->$primaryKey] = &$records[key($records)];
            }
        } while ($record = next($records));

        // remove all moved child nodes from top level
        $records = array_diff_key($records, array_flip($moved));

        return array_values($records);
    }
}
