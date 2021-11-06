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
     * @var array
     */
    protected $metadataDirs = [];

    /**
     * Metadata cache for active record classes
     * @var Storage[]
     */
    protected $metadata = [];

    /**
     * Active record relation list
     * @var RelationAbstract[]
     */
    protected $relations = [];

    /**
     * @var AdapterAbstract
     */
    protected $db;

    /**
     * @param AdapterAbstract $db
     */
    public function __construct(AdapterAbstract $db)
    {
        $this->db = $db;
    }

    /**
     * @return AdapterAbstract
     */
    public function getDb(): AdapterAbstract
    {
        return $this->db;
    }

    /**
     * Create and return query instance configured for fetching active record entities of concrete type
     * @param string $className
     * @return ActiveQuery
     */
    public function of($className): ActiveQuery
    {
        $query = new ActiveQuery($this->db, new $className($this));

        return $query->build();
    }

    /**
     * Add metadata dir
     * @param string $dir
     */
    public function addMetadataDir($dir)
    {
        if (!in_array($dir, $this->metadataDirs)) {
            $this->metadataDirs[] = $dir;
        }
    }

    /**
     * Get metadata object for concrete active record class
     * @param string $className
     * @return Storage
     */
    public function getMetadata($className): Storage
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
     * Get active record relation object
     * @param string $name Relation name
     * @param string $className Active record class
     * @return RelationAbstract
     * @throws \RuntimeException
     */
    public function getRelation(string $name, string $className)
    {
        if (!isset($this->relations[$name])) {
            $meta = $this->getMetadata($className)['relations'][$name] ?? [];
            $relation = null;

            if ($meta) {
                switch ($meta['type']) {
                    case 'hasOne':
                        $relation = new HasOneRelation($name, $this);
                        break;

                    case 'hasMany':
                        $relation = new HasManyRelation($name, $this);
                        break;

                    case 'hasManyJoint':
                        $relation = new HasManyJointRelation($name, $this);
                        break;

                    default:
                        throw new \RuntimeException(sprintf('The type of "%s" relation is unknown', $name));
                }

                $relation->build($meta);
            }

            $this->relations[$name] = $relation;
        }

        return $this->relations[$name];
    }

    /**
     * Load metadata from all registered dirs and store them as configuration objects
     */
    protected function loadMetadata()
    {
        $config = new Storage;

        foreach ($this->metadataDirs as $dir) {
            $configs = glob($dir . '/*.yml');

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
