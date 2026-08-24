<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class ErPost extends Record
{

    protected ?string $table = 'er_posts';

    public function user(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\ErUser', 'user_id', $options, $eager);
    }

}
