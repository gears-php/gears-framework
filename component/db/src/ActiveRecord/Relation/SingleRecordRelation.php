<?php

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveRecord;

interface SingleRecordRelation
{
    public function exec(mixed $ownerId): ?ActiveRecord;
}