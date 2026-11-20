<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncodedJsonKeys extends Encoded
{

    /**
     * Table
     * @var ?string
     */
    protected ?string $table = 'users_encoded';

    /**
     * JSON-encoded fields
     * @var array
     */
    protected array $jsonFields = ['info'];

    /**
     * JSON-encoded fields and the keys inside each that are encrypted
     * @var array
     */
    protected array $encryptedJsonKeys = ['info' => ['secret', 'pin']];

    /**
     * Cipher method
     * @var ?string
     */
    protected ?string $cipherMethod = 'aes-256-cbc';

    /**
     * Encrypted field key
     * @var ?string
     */
    protected ?string $key = 'vBTcBMBrauIpjy2oXhXOFxshW4//tXXnagOr2a+AqKI=';

}
