<?php

namespace Pop\Db\Test\TestAsset\Model;

use Pop\Db\Model\AbstractDataModel;

class UserWithJoin extends AbstractDataModel
{

    protected ?string $table = 'Pop\Db\Test\TestAsset\Table\Users';

    protected array $foreignTables = [
        'table'   => 'data_model_user_meta',
        'columns' => ['data_model_users.id' => 'data_model_user_meta.user_id']
    ];

}
