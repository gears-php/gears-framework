<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

use Gears\Db\ActiveRecord\Relation\RelationAbstract;

class LazyRecords implements \Iterator, \Countable
{
    private int $recordPosition = 0;
    /** @var ActiveRecord[] */
    protected array $records = [];

    public function __construct(private readonly ?RelationAbstract $relation, private readonly ActiveRecord $owner)
    {
    }

    public function current(): ActiveRecord
    {
        return $this->records[$this->recordPosition];
    }

    public function next(): void
    {
        ++$this->recordPosition;
    }

    public function key(): int
    {
        return $this->recordPosition;
    }

    public function valid(): bool
    {
        return isset($this->records[$this->recordPosition]);
    }

    public function rewind(): void
    {
        $this->recordPosition = 0;
        $this->records = $this->relation->exec($this->owner->getId());
    }

    public function count(): int
    {
        if (empty($this->records)) {
            $this->records = $this->relation->exec($this->owner->getId());
        }

        // todo change to count query
        return count($this->records);
    }

    /**
     * Set data records
     */
    public function set(array $records)
    {
        $this->records = $records;
    }

    public function save(): bool
    {
        $className = $this->relation->getMetadata()['class'];

        if (count(
            array_filter($this->records, function ($entry) use ($className) {
                return !($entry instanceof $className);
            })
        )) {
            throw new \DomainException(sprintf('All given records must be of %s type', $className));
        }

        foreach ($this->records as $record) {
            // todo bulk saving with single insert/update query
            echo('saved lazy record/ ');
            $record->save();
        }

        return true;
    }
}