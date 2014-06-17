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

//        $this->owner = $owner;
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
        $row[$this->name] = ['test'];
        $query = new Query($this->getTable()->getDb());
        $tableName = $this->getTable()->getTableName();
        $query->select('*', null, $tableName)
            ->from($tableName)
            ->join(
                $this->throughTable->getTableName(),
                $tableName . '_id',
                $tableName,
                $this->getTable()->getPrimaryKey()
            );

        $query->where(new Query\WhereAnd($this->getTable()->getDb()));
        $query->getWhere()->eq($this->owner->getTableName() . '_id', $row[$this->owner->getPrimaryKey()]);
        $row[$this->name] = $query->exec()->fetchAll();
    }
}
