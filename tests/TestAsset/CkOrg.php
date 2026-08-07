<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class CkOrg extends Record
{

    protected ?string $table = 'ck_orgs';
    protected array $primaryKeys = ['org_id', 'branch_id'];

    public function notes(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    public function firstNote(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    public function tickets(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\CkTicket', ['ticket_org_id', 'ticket_branch_id'], $options, $eager);
    }

    public function firstTicket(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\CkTicket', ['ticket_org_id', 'ticket_branch_id'], $options, $eager);
    }

}
