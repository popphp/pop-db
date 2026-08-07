<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class CkOrg extends Record
{

    protected ?string $table = 'ck_orgs';
    protected array $primaryKeys = ['org_id', 'branch_id'];

}
