<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class UserContacts extends Record
{

    protected $primaryKeys = ['user_id'];

    public function parent()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\Users', 'user_id');
    }

}
