<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class UsersEncoded2 extends Encoded
{

    protected $table = 'users_encoded';

    /**
     * Encrypted fields
     * @var array
     */
    protected $encryptedFields = ['ssn'];

    /**
     * Cipher method
     * @var string
     */
    protected $cipherMethod = null;

    /**
     * Encrypted field key
     * @var string
     */
    protected $key = null;

    /**
     * Encrypted field IV (base64-encoded)
     * @var string
     */
    protected $iv = null;

}