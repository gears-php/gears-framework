<?php
namespace Gears\Db\Table\Relation;

use Gears\Db\Query;
use Gears\Db\Table\TableAbstract;

class Through extends RelationAbstract
{
    /**
     * Linking table
     * @var TableAbstract
     */
    protected $throughTable;

    /**
     * {@inheritdoc}
     */
    public function __construct(TableAbstract $owner, array $metadata)
    {
        parent::__construct($owner, $metadata);
        $throughTableClassName = $metadata['through'];

        // build full relation table class name using namespace of the relation owner table class
        if (false === strpos($throughTableClassName, '\\')) {
            $throughTableClassName = preg_replace('/(\w+)$/', ucfirst($throughTableClassName), get_class($owner));
        }

        if (!isset(self::$tables[$throughTableClassName])) {
            self::$tables[$throughTableClassName] = new $throughTableClassName($owner->getDb());
        }

        $this->throughTable = self::$tables[$throughTableClassName];
    }

    /**
     * {@inheritdoc}
     */
    public function addTo(TableAbstract $table)
    {
    }

    /**
     * Build the query, fetch the data from the relation table and add them to the main row
     * {@inheritdoc}
     */
    public function addData(array &$row)
    {
        $tableName = $this->getTable()->getTableName();
        $query = $this->getTable()
            ->createQuery()
            ->join(
                $this->throughTable->getTableName(),
                $tableName . '_id',
                $tableName,
                $this->getTable()->getPrimaryKey()
            );

        $query->getWhere()->eq($this->owner->getTableName() . '_id', $row[$this->owner->getPrimaryKey()]);
        $row[$this->name] = $query->exec()->fetchAll();
    }
}
