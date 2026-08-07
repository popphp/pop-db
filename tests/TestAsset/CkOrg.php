<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class CkOrg extends Record
{

    protected ?string $table = 'ck_orgs';
    protected array $primaryKeys = ['org_id', 'branch_id'];

    public function notes(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\CkNote', ['org_id', 'branch_id'], $options, $eager);
    }

    public function firstNote(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\CkNote', ['org_id', 'branch_id'], $options, $eager);
    }

}
