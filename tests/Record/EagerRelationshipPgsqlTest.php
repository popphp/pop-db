<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Db;

class EagerRelationshipPgsqlTest extends EagerRelationshipTestCase
{

    protected function createDb(): AbstractAdapter
    {
        return Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
    }

}
