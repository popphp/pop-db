<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class DlParent extends Record
{

    protected ?string $table = 'dl_parents';

    public function children(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\DlChild', 'parent_id', $options, $eager);
    }

    public function firstChild(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\DlChild', 'parent_id', $options, $eager);
    }

}
