<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2015 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Db\ActiveRecord;

use PDO;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Db\Query\WhereAnd;
use Gears\Db\Query;

/**
 * Allows to build a query which will fetch data into Active Record class object(s)
 * @package Gears\Db
 */
class ActiveQuery extends Query
{
    /**
     * @var ActiveRecord
     */
    protected $activeRecord;

    /**
     * {@inheritdoc}
     * @param AdapterAbstract $db
     * @param ActiveRecord $activeRecord
     */
    public function __construct(AdapterAbstract $db, ActiveRecord $activeRecord)
    {
        parent::__construct($db);
        $this->activeRecord = $activeRecord;
    }

    /**
     * Get query subject active record
     * @return ActiveRecord
     */
    public function getActiveRecord(): ActiveRecord
    {
        return $this->activeRecord;
    }

    /**
     * Query ActiveRecord list
     * @return ActiveRecord[]
     */
    public function fetchAll(): array
    {
        return $this->exec()
            ->getStatement()
            ->fetchAll(
                PDO::FETCH_CLASS,
                get_class($this->activeRecord),
                [$this->activeRecord->getManager()]
            );
    }

    /**
     * Query a single ActiveRecord
     * @return ActiveRecord
     */
    public function fetchOne()
    {
        return $this->exec()
            ->getStatement()
            ->fetchObject(get_class($this->activeRecord), [$this->activeRecord->getManager()]) ?: null;
    }

    /**
     * @param int $id
     * @return ActiveRecord
     */
    public function fetchById($id)
    {
        $this->getWhere()->eq($this->activeRecord->getPrimaryKey(), $id);

        return $this->fetchOne();
    }

    /**
     * Build default query for fetching active record(s) using metadata information
     * @return ActiveQuery
     */
    public function build(): ActiveQuery
    {
        $meta = $this->activeRecord->getMetadata();

        return $this->select($meta['fields'], null, $meta['tableName'])
            ->from($meta['tableName'])
            ->where(new WhereAnd($this->db))
            ->order($meta['sortBy']);
    }
}
