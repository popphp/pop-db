<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Auth;

class UsersAuthWeakHash extends Auth
{

    protected ?string $table = 'users_auth';

    /**
     * Deliberately weak/outdated bcrypt cost, to exercise needsRehash()/rehash()
     * @var array
     */
    protected array $hashOptions = ['cost' => 4];

}
