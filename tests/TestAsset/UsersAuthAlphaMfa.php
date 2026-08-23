<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record\Auth;

class UsersAuthAlphaMfa extends Auth
{

    protected ?string $table = 'users_auth';

    protected array $mfaConfig = [
        'length'              => 6,
        'expires'             => 300,
        'alphanumeric'        => true,
        'mfa_code_field'      => 'mfa_code',
        'mfa_timestamp_field' => 'mfa_timestamp'
    ];

}
