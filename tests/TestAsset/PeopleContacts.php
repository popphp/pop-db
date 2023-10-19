<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class PeopleContacts extends Record
{

    protected array $primaryKeys = ['people_id'];

    public function parent()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\People', 'people_id');
    }

}
