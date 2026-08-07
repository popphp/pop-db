<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class GuardedUsers extends Record
{

    protected ?string $table  = 'users';
    protected array $guarded = ['logins'];

}