<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql;

class PredicateTest extends \PHPUnit_Framework_TestCase
{
    public function testRenderWithConditions()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("username LIKE '%admin'");

        $this->assertEquals('SELECT * FROM "users" WHERE ("username" LIKE \'%admin\')', $sql->render());
    }
}