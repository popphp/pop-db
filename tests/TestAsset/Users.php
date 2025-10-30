<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class Users extends Record
{

    public function userInfo(?array $options = null, $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\UserInfo', 'user_id', $options, $eager);
    }

    public function userContacts(?array $options = null, $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\UserContacts', 'user_id', $options, $eager);
    }

}
