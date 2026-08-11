<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class DlCategory extends Record
{

    protected ?string $table = 'dl_categories';

    public function parent(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\DlCategory', 'parent_id', $options, $eager);
    }

}
