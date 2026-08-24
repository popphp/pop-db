<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Db;

class EagerRelationshipMysqlTest extends EagerRelationshipTestCase
{

    protected function createDb(): AbstractAdapter
    {
        return Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
    }

}
