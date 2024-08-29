<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;
use Gears\Db\Db;
use JsonSerializable;

/**
 * Simple Active record pattern implementation
 * @package Gears\Db
 */
class ActiveRecord implements JsonSerializable
{
    use MetadataAware;

    public function __construct(protected ActiveManager $manager, private readonly Storage $metadata)
    {
    }

    public function getId(): mixed
    {
        return $this->{$this->getPrimaryKey()} ?? null;
    }

    /**
     * Initialize record state assigning its properties from db data.
     * Execute relations and assign each relation data to corresponding AR property.
     */
    public function init(array $dbData = []): static
    {
        !count($dbData) || $this->fill(array_combine($this->getFields(), $dbData));

        foreach ($this->getRelationsMetadata()->getKeys() as $relationName) {
            $this->$relationName = $this->manager
                ->getRelation($relationName, $this)
                ->lazy($this);
        }

        return $this;
    }

    /**
     * Fill record with given property values.
     */
    public function fill(array $props): void
    {
        foreach ($props as $prop => $value) {
            if ($read = $this->convertor($prop)?->read) {
                // use "read" convertor to transform db raw value to runtime format
                $this->$prop = $read($value);
            } else {
                $this->$prop = $value;
            }
        }
    }

    /**
     * Save record property data into db, including relations.
     * @return bool If saved successfully
     */
    public function save(): bool
    {
        $relationsMetadata = $this->getRelationsMetadata();
        $dirtyData = [];

        // first save single-object relations because we will need their PKeys
        foreach ($relationsMetadata->getKeys() as $prop) {
            $relationRecord = $this->$prop;
            if ($relationRecord instanceof ActiveRecord ) {
                ($relationsMetadata["$prop.cascade"] ?? true) && $relationRecord->save();
                $dirtyData[$relationsMetadata["$prop.foreign"]] = $relationRecord->getId();
            }
        }

        // now saving this record itself

        foreach ($this->getFields() as $alias => $field) {
            $fieldName = is_string($alias) ? $alias : $field;
            if ($write = $this->convertor($fieldName)?->write) {
                // use "write" convertor to trunsform runtime value to db raw format
                $dirtyData[$field] = $write($this?->$fieldName);
            } else {
                $dirtyData[$field] = $this->$fieldName ?? null;
            }
        }

        $primaryKey = $this->getPrimaryKey();
        $tableName = $this->getTableName();

        if (!empty($dirtyData[$primaryKey])) {
            $saved = $this->getDb()->update($tableName, $dirtyData, [$primaryKey => $this->$primaryKey]);
        } else {
            $saved = $this->getDb()->insert($tableName, [$dirtyData]);

            if ($saved) {
                $this->$primaryKey = $this->getDb()->getLastInsertId();
            }
        }

        // finally, saving LazyRecords because they need PK value of this record
        foreach ($relationsMetadata->getKeys() as $prop) {
            if ($this->$prop instanceof LazyRecords && ($relationsMetadata["$prop.cascade"] ?? true)) {
                $this->$prop->save();
            }
        }

        return (bool)$saved;
    }

    /**
     * Delete existing record data from db
     */
    public function delete(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        $deleted = false;

        if (isset($this->$primaryKey)) {
            $deleted = $this->getDb()->delete($this->getTableName(), [$primaryKey => $this->$primaryKey]);
        }

        return $deleted;
    }

    public function jsonSerialize(): array
    {
        $fields = $this->getFields();
        $data = array_combine($fields, array_map(fn($prop) => $this->$prop, $fields));
        $relationsMetadata = $this->getRelationsMetadata();

        if (!$relationsMetadata->getKeys() || $this->metadata['serialize_relations'] === false) {
            return $data;
        }

        static $isRelationSerialized = [];

        foreach ($relationsMetadata->get() as $name => $config) {
            if (($config['serialize'] ?? null) === false) {
                continue;
            }

            $cacheKey = implode(':', [
                get_class($this),
                $this->{$this->getPrimaryKey()},
                $name,
                // todo somehow get id of target record
            ]);

            if (!isset($isRelationSerialized[$cacheKey])) {
                $relational[$name] = $this->{$name};
            }

            $isRelationSerialized[$cacheKey] = true;
        }

        return array_merge($data, $relational ?? []);
    }

    /**
     * Get convertor object. Used for conversion of db value to model property value
     * when reading from db and backward transformation when writing to db.
     * @return object{read?: \Closure, write?: \Closure}
     */
    private function convertor(string $prop): ?object
    {
        return method_exists($this, $prop) ? (object)$this->$prop() : null;
    }

    private function getDb(): Db
    {
        return $this->manager->getDb();
    }
}
