<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class ErOrg extends Record
{

    protected ?string $table       = 'er_orgs';
    protected array   $primaryKeys = ['org_id', 'branch_id'];

    public function notes(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\ErNote', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    public function firstNote(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\ErNote', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

}
