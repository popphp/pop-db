<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql;

class PredicateTest extends \PHPUnit_Framework_TestCase
{

    public function testNotEqual()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("username != 'admin'");

        $this->assertEquals('SELECT * FROM "users" WHERE ("username" != \'admin\')', $sql->render());
    }

    public function testLike()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("username LIKE '%admin'");

        $this->assertEquals('SELECT * FROM "users" WHERE ("username" LIKE \'%admin\')', $sql->render());
    }

    public function testNotLike()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("username NOT LIKE '%admin'");

        $this->assertEquals('SELECT * FROM "users" WHERE ("username" NOT LIKE \'%admin\')', $sql->render());
    }

    public function testLessThan()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("id < 10");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" < 10)', $sql->render());
    }

    public function testLessThanOrEqualTo()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("id <= 10");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" <= 10)', $sql->render());
    }

    public function testBetween()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users');
        $sql->select()->where("id BETWEEN 10 AND 20");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" BETWEEN 10 AND 20)', $sql->render());
    }

    public function testNotBetween()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("id NOT BETWEEN 10 AND 20");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" NOT BETWEEN 10 AND 20)', $sql->render());
    }

    public function testIn()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("id IN (1, 2, 3, 4, 5)");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" IN (1, 2, 3, 4, 5))', $sql->render());
    }

    public function testNotIn()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("id NOT IN (1, 2, 3, 4, 5)");

        $this->assertEquals('SELECT * FROM "users" WHERE ("id" NOT IN (1, 2, 3, 4, 5))', $sql->render());
    }

    public function testNull()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("email IS NULL");

        $this->assertEquals('SELECT * FROM "users" WHERE ("email" IS NULL)', $sql->render());
    }

    public function testNotNull()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users')
            ->where("email IS NOT NULL");

        $this->assertEquals('SELECT * FROM "users" WHERE ("email" IS NOT NULL)', $sql->render());
    }

    public function testSetAndGetCombine()
    {
        $equal = new Sql\Predicate\EqualTo(['id', 1]);
        $equal->setCombine('OR');
        $this->assertEquals('OR', $equal->getCombine());
    }

    public function testAndNest()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $predicate = new Sql\Predicate($sql);
        $predicate->add('id >= 10');
        $predicate->nest()->add('username LIKE \'%admin\'');
        $predicate->andNest()->add('username NOT LIKE \'%staff\'');
        $this->assertEquals('(("username" LIKE \'%admin\') AND ("username" NOT LIKE \'%staff\')) AND ("id" >= 10)', $predicate->render());
    }

    public function testOrNest()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $predicate = new Sql\Predicate($sql);
        $predicate->add('id >= 10');
        $predicate->nest()->add('username LIKE \'%admin\'');
        $predicate->orNest()->add('username NOT LIKE \'%staff\'');
        $this->assertEquals('(("username" LIKE \'%admin\') OR ("username" NOT LIKE \'%staff\')) AND ("id" >= 10)', $predicate->render());
    }

    public function testNested()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $sql = $db->createSql();
        $predicate = new Sql\Predicate($sql);
        $predicate->add('id >= 10');
        $predicate->nest()->add('username LIKE \'%admin\'');
        $this->assertTrue($predicate->hasNest());
        $this->assertTrue($predicate->getNest(0)->isNested());
        $this->assertTrue($predicate->getNest(0)->hasPredicates());
        $this->assertInstanceOf('Pop\Db\Sql\Predicate', $predicate->getNest(0));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\Like', $predicate->getNest(0)->getLastPredicateSet());
    }

}