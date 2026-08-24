<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class ErNote extends Record
{

    protected ?string $table = 'er_notes';

    public function org(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\ErOrg', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

    public function orgOneOf(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\ErOrg', ['note_org_id', 'note_branch_id'], $options, $eager);
    }

}
