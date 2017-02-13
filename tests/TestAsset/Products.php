<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class Products extends Record
{

    protected $prefix = 'ph_';

    public function parentOrder()
    {
        return $this->belongsTo('Pop\Db\Test\TestAsset\Orders', 'order_id');
    }

}
