<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class PeopleInfo extends Record
{

    protected $primaryKeys = ['people_id'];

    public function parent()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\People', 'people_id');
    }

}
