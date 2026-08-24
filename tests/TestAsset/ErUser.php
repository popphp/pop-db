<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class ErUser extends Record
{

    protected ?string $table = 'er_users';

    public function info(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\ErUserInfo', 'user_id', $options, $eager);
    }

    public function posts(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\ErPost', 'user_id', $options, $eager);
    }

    public function role(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\ErRole', 'role_id', $options, $eager);
    }

}
