<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql;

class JoinTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $join = new Sql\Join($sql, 'user_info', ['id' => 'user_id'], 'LEFT JOIN');
        $this->assertInstanceOf('Pop\Db\Sql\Join', $join);
        $this->assertEquals('"user_info"', $join->getForeignTable());
        $this->assertEquals( ['id' => 'user_id'], $join->getColumns());
        $this->assertEquals('LEFT JOIN', $join->getJoin());
    }

    public function testForeignTableAlias()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $join = new Sql\Join($sql, ['foo' => 'user_info'], ['id' => 'user_id'], 'LEFT JOIN');
        $this->assertEquals('"user_info" AS "foo"', $join->getForeignTable());
    }

    public function testForeignTableSubSelect()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $select = new Sql\Select($db);
        $select->from('user_info', 'foo');
        $join = new Sql\Join($sql, $select, ['id' => 'user_id'], 'LEFT JOIN');
        $this->assertEquals('(SELECT * FROM "user_info") AS "foo"', $join->getForeignTable());
    }

    public function testRender()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')->join('user_info', ['users.id' => 'user_id']);
        $this->assertEquals('SELECT * FROM "users" JOIN "user_info" ON ("users"."id" = "user_id")', $sql->render());
    }

}