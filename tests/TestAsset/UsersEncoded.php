<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncoded extends Encoded
{

    /**
     * JSON-encoded fields
     * @var array
     */
    protected $jsonFields = ['info'];

    /**
     * PHP-serialized fields
     * @var array
     */
    protected $phpFields = ['notes'];

    /**
     * Base64-encoded fields
     * @var array
     */
    protected $base64Fields = ['file'];

    /**
     * Password-hashed fields
     * @var array
     */
    protected $hashFields = ['password'];

    /**
     * Encrypted fields
     * @var array
     */
    protected $encryptedFields = ['ssn'];

    /**
     * Hash algorithm
     * @var string
     */
    protected $hashAlgorithm = PASSWORD_BCRYPT;

    /**
     * Hash options
     * @var array
     */
    protected $hashOptions = ['cost' => 15];

    /**
     * Cipher method
     * @var string
     */
    protected $cipherMethod = 'AES-128-CBC';

    /**
     * Encrypted field key
     * @var string
     */
    protected $key = '992a889eb02ec33b251fa6d9cb2cb4bec32c7ac7';

    /**
     * Encrypted field IV (base64-encoded)
     * @var string
     */
    protected $iv = 'zR/wFGfs5Dr63/d8AnrDBg==';

}