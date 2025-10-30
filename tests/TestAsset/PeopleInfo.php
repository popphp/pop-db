<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class PeopleInfo extends Record
{

    protected array $primaryKeys = ['people_id'];

    public function parent()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\People', 'people_id');
    }

    public function people(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\People', 'people_id', $options, $eager);
    }

}
