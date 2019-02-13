<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class UserInfo extends Record
{
    protected $prefix = 'ph_';

    protected $primaryKeys = ['user_id'];

    public function info()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\Users', 'id');
    }

    public function user()
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\Users', 'user_id');
    }

}
