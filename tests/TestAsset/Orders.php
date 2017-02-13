<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class Orders extends Record
{

    protected $prefix = 'ph_';

    public function products(array $options = null, $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\Products', 'order_id', $options, $eager);
    }

}
