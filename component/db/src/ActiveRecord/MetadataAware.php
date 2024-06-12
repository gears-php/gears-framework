<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;

trait MetadataAware
{
    private readonly Storage $metadata;

    public function getFields(): array
    {
        $fields = $this->metadata['fields']->raw();

        if ($parentKey = $this->getParentKey()) {
            array_unshift($fields, $parentKey);
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
        return $this->metadata['primaryKey'];
    }

    /**
     * Get table name from metadata
     * @return string
     */
    public function getTableName(): string
    {
        return $this->getMetadata()['tableName'];
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
    public function getParentKey(): ?string
    {
        return $this->metadata['parentKey'] ?? null;
    }

    public function getMetadata(): Storage
    {
        return $this->metadata;
    }
}