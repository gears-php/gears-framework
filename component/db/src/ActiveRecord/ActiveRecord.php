<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;
use Gears\Db\Adapter\AdapterAbstract;
use JsonSerializable;

/**
 * Simple Active record pattern implementation
 * @package Gears\Db
 */
class ActiveRecord implements JsonSerializable
{
    /**
     * Stores pristine (db persistent) property values
     */
    protected array $data = [];

    /**
     * Stores yet unsaved modified property values
     */
    protected array $dirty = [];

    /**
     * Get primary key from metadata
     */
    public function getPrimaryKey(): string
    {
        return $this->getMetadata()['primaryKey'];
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
     * Get metadata object for concrete active record class.
     * @return Storage
     */
    public function getMetadata(): Storage
    {
        return $this->manager->getMetadata(get_called_class());
    }

    /**
     * Get metadata part for AR relations.
     */
    public function getRelationsMetadata(): ?array
    {
        return $this->getMetadata()['relations'];
    }

    /**
     * @return ActiveManager
     */
    public function getManager(): ActiveManager
    {
        return $this->manager;
    }

    /**
     * On construction, we need to fixate data because when PDO initializes object
     * with existing db record data they go to dirty only (via magic __set)
     * @see fixate
     * @see __set
     */
    public function __construct(protected ActiveManager $manager)
    {
        $this->fixate();
    }

    /**
     * Fill record with given property values.
     */
    public function fill(array $props): void
    {
        $this->dirty = array_merge($this->dirty, $props);
    }

    /**
     * Set object property value
     */
    public function __set(string $prop, mixed $value)
    {
        $this->dirty[$prop] = $value;
    }

    /**
     * Get object property value or get relation active record(s) in case there is relation defined with given name
     */
    public function __get(string $prop)
    {
        if ($relation = $this->manager->getRelation($prop, $this)) {
            return $relation->exec();
        }

        if (isset($this->dirty[$prop])) {
            return $this->dirty[$prop];
        }

        if (isset($this->data[$prop])) {
            return $this->data[$prop];
        }

        return null;
    }

    /**
     * Remove object property value
     * @param string $prop
     */
    public function __unset(string $prop)
    {
        if (isset($this->data[$prop])) {
            unset($this->data[$prop]);
        }

        if (isset($this->dirty[$prop])) {
            unset($this->dirty[$prop]);
        }
    }

    /**
     * Save object property data into db
     * @return bool If saved successfully
     * @see fixate
     */
    public function save(): bool
    {
        $metadata = $this->getMetadata();
        $data = [];

        foreach ($this->dirty as $prop => $value) {
            if (in_array($prop, $metadata['fields'])) {
                $data[$prop] = $value;
            } elseif ($field = $metadata['fields'][$prop] ?? null) {
                $data[$field] = $value;
            }
        }
        $primaryKey = $this->getPrimaryKey();
        $tableName = $this->getTableName();

        if (isset($this->data[$primaryKey])) {
            $saved = $this->getDb()->update($tableName, $data, [$primaryKey => $this->data[$primaryKey]]);
        } else {
            $saved = $this->getDb()->insert($tableName, [$data]);

            if ($saved) {
                // todo read entire record data??
                $this->dirty[$primaryKey] = $this->getDb()->getLastInsertId();
            }
        }

        if ($saved) {
            $this->fixate();
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

        if (isset($this->data[$primaryKey])) {
            $deleted = $this->getDb()->delete($this->getTableName(), [$primaryKey => $this->data[$primaryKey]]);

            if ($deleted) {
                $this->data = $this->dirty = [];
            }
        }

        return $deleted;
    }

    public function jsonSerialize()
    {
        $metadata = $this->getRelationsMetadata();

        if (!$metadata || false) { // todo add configuration option to serialize with relations
            return $this->data;
        }

        static $isRelationSerialized = [];

        foreach ($metadata as $name => $relation) {
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

        return array_merge($this->data, $relational ?? []);
    }

    /**
     * Move dirty data to pristine one. Typically used after record saving
     */
    protected function fixate()
    {
        $this->data = array_merge($this->data, $this->dirty);
        $this->dirty = [];
    }

    /**
     * @return AdapterAbstract
     */
    protected function getDb(): AdapterAbstract
    {
        return $this->manager->getDb();
    }
}
