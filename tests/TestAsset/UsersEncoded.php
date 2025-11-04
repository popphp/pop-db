<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncoded extends Encoded
{

    /**
     * JSON-encoded fields
     * @var array
     */
    protected array $jsonFields = ['info'];

    /**
     * PHP-serialized fields
     * @var array
     */
    protected array $phpFields = ['notes'];

    /**
     * Base64-encoded fields
     * @var array
     */
    protected array $base64Fields = ['file'];

    /**
     * Password-hashed fields
     * @var array
     */
    protected array $hashFields = ['password'];

    /**
     * Encrypted fields
     * @var array
     */
    protected array $encryptedFields = ['ssn'];

    /**
     * Hash algorithm
     * @var string
     */
    protected string $hashAlgorithm = PASSWORD_BCRYPT;

    /**
     * Hash options
     * @var array
     */
    protected array $hashOptions = ['cost' => 12];

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
