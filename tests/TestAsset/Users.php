<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class Users extends Record
{
    protected $prefix = 'ph_';

    public function info()
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\UserInfo', 'user_id');
    }

}
