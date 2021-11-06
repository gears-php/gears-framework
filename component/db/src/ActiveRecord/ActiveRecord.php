<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Storage\Storage;
use Gears\Db\Adapter\AdapterAbstract;

/**
 * Simple Active record pattern implementation
 * @package Gears\Db
 */
class ActiveRecord implements \JsonSerializable
{
    /**
     * Stores pristine (db persistent) property values
     * @var array
     */
    protected $data = [];

    /**
     * Stores yet unsaved modified property values
     * @var array
     */
    protected $dirty = [];

    /**
     * @var ActiveManager
     */
    protected $manager;

    /**
     * Get primary key from metadata
     * @return string
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
     * Get metadata object for concrete active record class
     * @return Storage
     */
    public function getMetadata(): Storage
    {
        return $this->manager->getMetadata(get_called_class());
    }

    /**
     * @return ActiveManager
     */
    public function getManager(): ActiveManager
    {
        return $this->manager;
    }

    /**
     * On construction we need to fixate data because when PDO initializes object
     * with existing db record data they go to dirty only (via magic __set)
     * @param ActiveManager $manager
     * @see fixate
     * @see __set
     */
    public function __construct(ActiveManager $manager)
    {
        $this->manager = $manager;
        $this->fixate();
    }

    /**
     * Set object property value
     * @param string $prop
     * @param mixed $value
     */
    public function __set(string $prop, $value)
    {
        $this->dirty[$prop] = $value;
    }

    /**
     * Get object property value or get relation active record(s) in case there is relation defined with given name
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        if ($relation = $this->manager->getRelation($prop, get_called_class())) {
            return $relation->exec($this);
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
        $meta = $this->getMetadata();
        $data = [];

        foreach ($this->dirty as $prop => $value) {
            if ($field = $meta['fields'][$prop]) {
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
     * @return bool
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

        return (bool)$deleted;
    }

    public function jsonSerialize()
    {
        return $this->data;
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
