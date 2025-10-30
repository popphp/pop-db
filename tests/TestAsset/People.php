<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class People extends Record
{

    public function peopleInfo(?array $options = null, $eager = false)
    {
        return $this->hasOne('Pop\Db\Test\TestAsset\PeopleInfo', 'people_id', $options, $eager);
    }

    public function peopleContacts(?array $options = null, $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\PeopleContacts', 'people_id', $options, $eager);
    }

}
