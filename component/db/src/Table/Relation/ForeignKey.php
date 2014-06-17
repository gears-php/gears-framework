<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Db\Table\Relation;

use Gears\Db\Table\TableAbstract;

/**
 * Object relation of some particular table to another one through the foreign key field
 * @package    Gears\Db
 * @subpackage Database
 */
class ForeignKey extends RelationAbstract
{
    /**
     * Primary key of the relation foreign table
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Foreign key of the relation owner table
     * @var string
     */
    protected $foreignKey = '';

    /**
     * {@inheritdoc}
     */
    public function __construct(TableAbstract $owner, array $metadata)
    {
        parent::__construct($owner, $metadata);
        $this->foreignKey = $metadata['foreign'];

        if (isset($metadata['primary'])) {
            $this->primaryKey = $metadata['primary'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addTo(TableAbstract $table)
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
