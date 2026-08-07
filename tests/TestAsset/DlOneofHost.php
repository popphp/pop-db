<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class DlOneofHost extends Record
{

    protected ?string $table = 'dl_oneof_hosts';

    public function child(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Pop\Db\Test\TestAsset\DlChild', 'child_id', $options, $eager);
    }

}
