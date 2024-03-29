<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

declare(strict_types=1);

namespace Gears\Db\Table\Relation;

use Gears\Db\Table\TableAbstract;

/**
 * Object relation of some particular table to another one via the foreign key field
 * @package    Gears\Db
 * @subpackage Database
 */
class HasOneRelation extends RelationAbstract
{
    /**
     * Primary key of the relation target table
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Foreign key of the relation owner table
     * @var string
     */
    protected $foreignKey;

    /**
     * {@inheritdoc}
     */
    public function __construct($relationName, TableAbstract $owner, array $metadata)
    {
        parent::__construct($relationName, $owner, $metadata);
        $this->foreignKey = $metadata['foreign'];

        if (isset($metadata['primary'])) {
            $this->primaryKey = $metadata['primary'];
        }
    }

    /**
     * Get foreign key value
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * {@inheritdoc}
     */
    public function addTo(TableAbstract $table): void
    {
        // add join condition to link relation table with the given one
        $table->getQuery()->join(
            [$this->name => $this->getTable()->getTableName()], // alias => name of joined table
            $this->primaryKey, // joined table pk used for linking
            $table->getTableName(),
            $this->foreignKey // owner table foreign key
        );
    }

    /**
     * {@inheritdoc}
     * Add the row data fetched from the relational table using foreign key
     */
    public function addData(array &$row)
    {
        $row[$this->name] = $this->table->fetchRow($row[$this->foreignKey]);
        unset($row[$this->foreignKey]); // remove original table foreign field
    }
}
