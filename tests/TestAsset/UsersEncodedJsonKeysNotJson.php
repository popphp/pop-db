<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncodedJsonKeysNotJson extends Encoded
{

    /**
     * Table
     * @var ?string
     */
    protected ?string $table = 'users_encoded';

    /**
     * JSON-encoded fields and the keys inside each that are encrypted (misconfigured: 'notes' is not a JSON field)
     * @var array
     */
    protected array $encryptedJsonKeys = ['notes' => ['secret']];

}
