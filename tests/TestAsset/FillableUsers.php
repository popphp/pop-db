<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class FillableUsers extends Record
{

    protected ?string $table   = 'users';
    protected array $fillable = ['username', 'email'];

}
