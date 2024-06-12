<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveManager;
use Gears\Db\ActiveRecord\ActiveQuery;
use Gears\Db\ActiveRecord\ActiveRecord;
use Gears\Db\ActiveRecord\LazyRecords;
use Gears\Storage\Storage;

/**
 * Base class of any concrete active record relation type
 * @package Gears\Db\ActiveRecord
 */
abstract class RelationAbstract
{
    /** Query instance used for relational records fetching */
    protected ActiveQuery $query;

    /** Construct query for fetching relation record(s) */
    abstract public function buildQuery();

    /**
     * Execute query against given target record and return relational one(s).
     * Should return single record or records array depending on relation type
     * implementation.
     *
     * @return ActiveRecord[]|ActiveRecord
     */
    abstract public function exec(mixed $ownerId): mixed;

    public function lazy(ActiveRecord $owner): mixed
    {
        if (!$this instanceof SingleRecordRelation) {
            return new LazyRecords($this, $owner);
        }

        if (!$owner->getId()) {
            return $this->metadata['nullable'] ?? false
                ? null
                : $this->query->createRecord();
        }

        return $this->exec($owner->getId());
    }

    public function __construct(
        protected Storage $metadata,
        protected Storage $ownerMetadata,
        protected ActiveManager $manager
    ) {
    }

    public function getMetadata(): Storage
    {
        return $this->metadata;
    }
}