<?php

namespace Gears\Db\ActiveRecord;

use Gears\Db\ActiveRecord\Relation\HasManyJointRelation;
use Gears\Db\ActiveRecord\Relation\HasManyRelation;
use Gears\Db\ActiveRecord\Relation\HasOneRelation;
use Gears\Db\Db;
use Gears\Storage\Reader\Exception\FileNotFound;
use RuntimeException;
use Gears\Db\ActiveRecord\Relation\RelationAbstract;
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
     * @var RelationAbstract[][]
     */
    protected array $relations = [];


    public function __construct(protected Db $db)
    {
    }

    public function getDb(): Db
    {
        return $this->db;
    }

    public function createRecord(string $className): ActiveRecord
    {
        return (new $className($this, $this->getMetadata($className)))->init([]);
    }

    /**
     * Create and return query instance configured for fetching active record entities of concrete type
     */
    public function createQuery(string $className): ActiveQuery
    {
        $query = new ActiveQuery($this->db, $this, $this->getMetadata($className));

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
     * @param string $relationName Relation name
     * @param ActiveRecord $owner Relation owner
     * @throws RuntimeException
     */
    public function getRelation(string $relationName, ActiveRecord $owner): ?RelationAbstract
    {
        $className = get_class($owner);
        $metadata = $this->getMetadata($className)['relations'][$relationName] ?? null;

        if (!$metadata) {
            return null;
        }

        if (isset($this->relations[$className][$relationName])) {
            return $this->relations[$className][$relationName];
        }

        $ownerMetadata = $owner->getMetadata()->raw();
        $relation = match ($metadata['type']) {
            'hasOne' => new HasOneRelation($metadata, $ownerMetadata, $this),
            'hasMany' => new HasManyRelation($metadata, $ownerMetadata, $this),
            'hasManyJoint' => new HasManyJointRelation($metadata, $ownerMetadata, $this),
            default => throw new RuntimeException(sprintf('The type of "%s" relation is unknown', $relationName)),
        };

        $relation->buildQuery();

        return $this->relations[$className][$relationName] = $relation;
    }

    /** Find single active record by given id */
    public function find(string $className, mixed $id): ?ActiveRecord
    {
        return $this->createQuery($className)->fetchById((string)$id);
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
                try {
                    $config->load($file);
                } catch (FileNotFound $e) {
                    throw new RuntimeException('Can not load metadata form file', 0, $e);
                }

                if ($config['class']) {
                    $this->metadata[$config['class']] = $config->get();
                } else {
                    throw new RuntimeException(
                        sprintf('ActiveRecord `class` definition not found in %s metadata file', $file)
                    );
                }
            }
        }
    }
}
