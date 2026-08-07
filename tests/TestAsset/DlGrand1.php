<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class DlGrand1 extends Record
{

    protected ?string $table = 'dl_grand1';

    public function owner(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\DlChild', 'child_id', $options, $eager);
    }

}
