<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class DlChild extends Record
{

    protected ?string $table = 'dl_children';

    public function parentRecord(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\DlParent', 'parent_id', $options, $eager);
    }

    public function grand1(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\DlGrand1', 'child_id', $options, $eager);
    }

    public function grand2(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\DlGrand2', 'child_id', $options, $eager);
    }

}
