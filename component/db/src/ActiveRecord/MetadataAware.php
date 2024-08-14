<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;

trait MetadataAware
{
    private readonly Storage $metadata;

    public function getFields(): array
    {
        $fields = $this->metadata['fields'];

        if (is_subclass_of($this->getClassName(), ActiveNode::class) ) {
            array_unshift($fields, $this->getParentKey());
        }

        array_unshift($fields, $this->getPrimaryKey());

        return array_unique($fields);
    }

    public function getClassName(): string
    {
        return $this->metadata['class'];
    }

    /**
     * Get primary key from metadata
     */
    public function getPrimaryKey(): string
    {
        return $this->metadata['primaryKey'] ?? 'id';
    }

    /**
     * Get table name from metadata
     * @return string
     */
    public function getTableName(): string
    {
        return $this->metadata['tableName'];
    }

    /**
     * Get metadata part for AR relations.
     */
    public function getRelationsMetadata(): Storage
    {
        return $this->metadata->get('relations');
    }

    /**
     * Get parent key from metadata (ActiveNode subtype)
     */
    public function getParentKey(): string
    {
        return $this->metadata['parentKey'] ?? 'parent_id';
    }

    public function getMetadata(): Storage
    {
        return $this->metadata;
    }
}