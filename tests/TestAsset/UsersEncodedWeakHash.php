<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncodedWeakHash extends Encoded
{

    protected ?string $table = 'users_encoded';

    /**
     * Password-hashed fields
     * @var array
     */
    protected array $hashFields = ['password'];

    /**
     * Deliberately weak/outdated bcrypt cost, to exercise needsRehash()/rehash()
     * @var array
     */
    protected array $hashOptions = ['cost' => 4];

}
