<?php

namespace Pop\Db\Test\TestAsset\Model;

use Pop\Db\Model\AbstractDataModel;

class User extends AbstractDataModel
{

    protected array $requirements = ['username'];

}
