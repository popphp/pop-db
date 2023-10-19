<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncoded2 extends Encoded
{

    protected ?string $table = 'users_encoded';

    /**
     * Encrypted fields
     * @var array
     */
    protected array $encryptedFields = ['ssn'];

    /**
     * Cipher method
     * @var ?string
     */
    protected ?string $cipherMethod = null;

    /**
     * Encrypted field key
     * @var ?string
     */
    protected ?string $key = null;

    /**
     * Encrypted field IV (base64-encoded)
     * @var ?string
     */
    protected ?string $iv = null;

}