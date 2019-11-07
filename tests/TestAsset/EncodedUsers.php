<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Encoded;

class EncodedUsers extends Encoded
{
    protected $prefix = 'ph_';

    protected $jsonFields      = ['info'];
    protected $phpFields       = ['metadata'];
    protected $base64Fields    = ['encoded'];
    protected $hashFields      = ['password'];
    protected $encryptedFields = ['ssn'];
    protected $cipherMethod    = 'AES-256-CBC';
    protected $key             = '992a889eb02ec33b251fa6d9cb2cb4bec32c7ac7';
    protected $iv              = 'onK1Uh/ODgIlNCwZVCqhyQ==';

}
