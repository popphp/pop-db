<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class CkNote extends Record
{

    protected ?string $table = 'ck_notes';

    public function orgOneOf(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\CkOrg', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    public function org(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\CkOrg', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    // Deliberately mismatched: 1 FK column against CkOrg's 2-column composite PK.
    public function badOrg(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\CkOrg', 'note_org_id', $options, $eager);
    }

    // Deliberately mismatched: 1 FK column against CkOrg's 2-column composite PK.
    public function badOrgBelongsTo(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\CkOrg', ['note_org_id'], $options, $eager);
    }

}
