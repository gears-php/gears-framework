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
 * Allows to build a query which will fetch data into Active Record class object(s)
 * @package Gears\Db
 */
class ActiveQuery extends Query
{
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

    /**
     * Query ActiveRecord list
     * @return ActiveRecord[]
     */
    public function fetchAll(): array
    {
        return $this->exec()->getStatement()->fetchAll(PDO::FETCH_FUNC, [$this, 'createRecord']);
    }

    /**
     * Get ActiveNode`s tree list where all child nodes put under their parents.
     */
    public function fetchTree(): array
    {
        if (!(new $this->metadata['class']) instanceof ActiveNode) {
            throw new \LogicException(
                sprintf('Class %s must implement ActiveNode for tree operations', $this->metadata['class'])
            );
        }

        array_unshift($this->select, $this->db->escapeIdentifier($this->metadata['primaryKey']));
        $records = $this->fetchAll();

        if (!$records) {
            return [];
        }

        /** @var ActiveNode[] $refs */
        $refs = $moved = [];
        $record = current($records);

        // build tree from a flat record set
        // todo fix tree logic
        do {
            /** @var ActiveNode $record */
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
        $records = $this->exec()
            ->getStatement()
            ->fetchAll(PDO::FETCH_FUNC, [$this, 'createRecord']);
        return count($records) == 1 ? $records[0] : null;
    }

    public function fetchById(string $id): ?ActiveRecord
    {
        $this->getWhere()->eq($this->metadata['primaryKey'], $id);

        return $this->fetchOne();
    }

    /**
     * Build default query for fetching active record(s) using metadata information
     */
    public function build(): ActiveQuery
    {
        return $this->select($this->metadata['fields']->raw(), null, $this->metadata['tableName'])
            ->from($this->metadata['tableName'])
            ->where(new WhereAnd($this->db))
            ->order($this->metadata['sortBy']->raw(), $this->metadata['tableName']);
    }

    public function createRecord(...$dbData): ActiveRecord
    {
        /** @var ActiveRecord $record */
        $record = new $this->metadata['class']($this->manager);
        return $record->init($dbData);
    }
}
