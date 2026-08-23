<?php

namespace Pop\Db\Test\TestAsset\Model;

use Pop\Db\Model\AbstractDataModel;

class UserWithDefaultSelect extends AbstractDataModel
{

    protected ?string $table = 'Pop\Db\Test\TestAsset\Table\Users';

    protected array $selectColumns = ['id', 'username'];

}
