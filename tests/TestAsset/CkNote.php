<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class CkNote extends Record
{

    protected ?string $table = 'ck_notes';

    public function orgOneOf(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\CkOrg', ['org_id', 'branch_id'], $options, $eager);
    }

    public function org(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\CkOrg', ['org_id', 'branch_id'], $options, $eager);
    }

}
