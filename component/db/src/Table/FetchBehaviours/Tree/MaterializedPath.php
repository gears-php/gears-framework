<?php

namespace Gears\Db\Table\FetchBehaviours\Tree;

use Gears\Db\Query;

trait MaterializedPath
{
    private $pathSeparator = '/';

    public function fetchAll(Query $query = null)
    {
        $query->select([$this->getPrimaryKey(), '*'], null, $this->getTableName());
        $records = $query->exec()->fetchAssoc(\PDO::FETCH_OBJ);

        $refs = $filter = [];
        $record = current($records);

        // build tree from a flat record set
        do {
            $parentId = $record->parent_id;

            if (isset($refs[$parentId])) {
                // put node as a child to a parent
                $refs[$parentId]->_children[] = $record;
                $childrenCount = count($refs[$parentId]->_children);
                $refs[$record->id] = &$refs[$parentId]->_children[$childrenCount - 1];
                $filter[] = $record->id;
            } else {
                // top level node
                $refs[$record->id] = &$records[key($records)];
            }

        } while ($record = next($records));

        // remove all moved child nodes from top level
        $records = array_diff_key($records, array_flip($filter));

        return array_values($records);
    }
}
