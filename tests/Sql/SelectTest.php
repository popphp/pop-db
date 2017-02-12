<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql;

class SelectTest extends \PHPUnit_Framework_TestCase
{

    public function testRenderWithValues()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select([0 => 'username', 'address' => 'email'])->from('users');
        $this->assertEquals('SELECT "username", "email" AS "address" FROM "users"', $sql->render());
    }

    public function testRenderWithSubSelect()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sub = new Sql\Select($db);
        $sub->from('user_info', 'info');
        $sql->select([0 => 'username', 'address' => 'email'])->from($sub);
        $this->assertEquals('SELECT "username", "email" AS "address" FROM (SELECT * FROM "user_info") AS "info"', $sql->render());
    }

    public function testRenderWithConditions()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where('id >= 1')
            ->groupBy('id')
            ->having('user_id > 5')
            ->orderBy('id DESC')
            ->limit(10)
            ->offset(20);

        $this->assertEquals(
            'SELECT * FROM "users" WHERE ("id" >= 1) HAVING ("user_id" > 5) GROUP BY "id" ORDER BY "id DESC" ASC LIMIT 10 OFFSET 20',
            $sql->render()
        );
    }

}