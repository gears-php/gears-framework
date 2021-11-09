<?php

namespace Gears\Db\ActiveRecord;

use Gears\Db\ActiveRecord\Relation\HasManyJointRelation;
use Gears\Db\ActiveRecord\Relation\HasManyRelation;
use Gears\Db\ActiveRecord\Relation\HasOneRelation;
use RuntimeException;
use Gears\Db\ActiveRecord\Relation\RelationAbstract;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Storage\Storage;

/**
 * High level active record management
 * @package Gears\Db
 */
class ActiveManager
{
    /**
     * List of directories where to look for metadata configuration files
     */
    protected array $metadataDirs = [];

    /**
     * Metadata cache for active record classes
     * @var Storage[]
     */
    protected array $metadata = [];

    /**
     * Active record relation list
     * @var RelationAbstract[]
     */
    protected array $relations = [];

    /**
     * @param AdapterAbstract $db
     */
    public function __construct(protected AdapterAbstract $db)
    {
    }

    /**
     * @return AdapterAbstract
     */
    public function getDb(): AdapterAbstract
    {
        return $this->db;
    }

    public function create(string $className): ActiveRecord
    {
        return new $className($this);
    }

    /**
     * Create and return query instance configured for fetching active record entities of concrete type
     */
    public function of(string $className): ActiveQuery
    {
        $query = new ActiveQuery($this->db, new $className($this));

        return $query->build();
    }

    /**
     * Add metadata dir
     */
    public function addMetadataDir(string $dir): void
    {
        if (!in_array($dir, $this->metadataDirs)) {
            $this->metadataDirs[] = $dir;
        }
    }

    /**
     * Set several metadata dirs
     */
    public function setMetadataDirs(array $dirs): void
    {
        array_map(fn($dir) => $this->addMetadataDir($dir), $dirs);
    }

    /**
     * Get metadata object for concrete active record class
     */
    public function getMetadata(string $className): Storage
    {
        if (!$this->metadata) {
            $this->loadMetadata();
        }

        if (!isset($this->metadata[$className])) {
            throw new RuntimeException(sprintf('ActiveRecord metadata not found for %s class', $className));
        }

        return $this->metadata[$className];
    }

    /**
     * Get relation object for given active record relation name.
     *
     * @param string $name Relation name
     * @param ActiveRecord $owner Relation owner
     * @throws RuntimeException
     */
    public function getRelation(string $name, ActiveRecord $owner): RelationAbstract|null
    {
        $className = get_class($owner);
        $metadata = $this->getMetadata($className)['relations'][$name] ?? null;

        if (!$metadata) {
            return null;
        }

        $relationName = "$className:$name";

        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $relation = match ($metadata['type']) {
            'hasOne' => new HasOneRelation($name, $owner),
            'hasMany' => new HasManyRelation($name, $owner),
            'hasManyJoint' => new HasManyJointRelation($name, $owner),
            default => throw new RuntimeException(sprintf('The type of "%s" relation is unknown', $name)),
        };

        $relation->build($metadata);

        return $this->relations[$relationName] = $relation;
    }

    /**
     * Load metadata from all registered dirs and store them as configuration objects
     */
    protected function loadMetadata(): void
    {
        $config = new Storage;

        foreach ($this->metadataDirs as $dir) {
            $configs = glob($dir . '/*' . $config->getReader()->getFileExt());

            foreach ($configs as $file) {
                $config->load($file);

                if ($config['class']) {
                    $this->metadata[$config['class']] = $config->getObj();
                } else {
                    throw new RuntimeException(sprintf('ActiveRecord `class` definition missed in %s metadata file', $file));
                }
            }
        }
    }
}
