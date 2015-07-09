<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Sql;

class SqlTest extends \PHPUnit_Framework_TestCase
{

    public function testPdoSqlite()
    {
        $db = Db::connect('pdo', ['database' => __DIR__  . '/tmp/db.sqlite', 'type' => 'sqlite']);
        $sql = new Sql($db, 'ph_users');
        $sql->setQuoteId(Sql::DOUBLE_QUOTE);
        $sql->into('ph_users');
        $this->assertEquals(Sql::DOUBLE_QUOTE, $sql->getQuoteId());
        $this->assertEquals('ph_users', $sql->getTable());
        $this->assertTrue($sql->hasTable());
        $this->assertFalse($sql->hasAlias());
        $this->assertNull($sql->getAlias());
        $this->assertInstanceOf('Pop\Db\Sql', $sql);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $sql->getDb());
    }

    public function testGetSql()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select();
        $s = $sql->render(true);
        $this->assertEquals('SELECT * FROM "ph_users"', $s);
        $this->assertEquals('SELECT * FROM "ph_users"', $sql->getSql());
    }

    public function testRenderException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');
        $s   = $sql->render(true);
    }

    public function testOrderBy()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select('username')->orderBy('id', 'ASC');
        $this->assertEquals('SELECT "username" FROM "ph_users" ORDER BY "id" ASC', $sql->render(true));

        $sql->select('username')->orderBy(['username', 'email'], 'ASC');
        $this->assertEquals('SELECT "username" FROM "ph_users" ORDER BY "username", "email" ASC', $sql->render(true));

        $sql->select('username')->orderBy('username, email', 'ASC');
        $this->assertEquals('SELECT "username" FROM "ph_users" ORDER BY "username", "email" ASC', $sql->render(true));

        $sql->select('username')->orderBy('id', 'RAND');
        $this->assertEquals('SELECT "username" FROM "ph_users" ORDER BY  RANDOM()', $sql->render(true));
    }

    public function testOffset()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select('username')->offset(25);
        $this->assertEquals('SELECT "username" FROM "ph_users" OFFSET 25', $sql->render(true));
    }

    public function testSelect()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select('username');
        $s = $sql->render(true);
        $this->assertEquals('SELECT "username" FROM "ph_users"', $s);
        $this->assertEquals('SELECT "username" FROM "ph_users"', $sql->getSql());
        $this->assertInstanceOf('Pop\Db\Sql', $sql->select()->getSql());
    }

    public function testSelectJoin()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select()->join('ph_user_info', ['ph_users.id' => 'ph_user_info.user_id']);

        $this->assertEquals('SELECT * FROM "ph_users" LEFT JOIN "ph_user_info" ON ("ph_users"."id" = "ph_user_info"."user_id")', $sql->render(true));
    }

    public function testSelectDistinct()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select('email')->distinct();

        $this->assertEquals('SELECT DISTINCT "email" FROM "ph_users"', $sql->render(true));
    }

    public function testSelectGroupBy()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select()->groupBy('email');
        $this->assertEquals('SELECT * FROM "ph_users" GROUP BY "email"', $sql->render(true));

        $sql->select()->groupBy('id, email');
        $this->assertEquals('SELECT * FROM "ph_users" GROUP BY "id", "email"', $sql->render(true));

        $sql->select()->groupBy(['id', 'email']);
        $this->assertEquals('SELECT * FROM "ph_users" GROUP BY "id", "email"', $sql->render(true));
    }

    public function testSelectHaving()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select()->having('id > 0');
        $this->assertEquals('SELECT * FROM "ph_users" HAVING ("id" > 0)', $sql->render(true));
    }

    public function testSelectWhereNest()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->select()->where('id > 0');
        $sql->select()->where->nest()->add('active != 0')->add('verified = 1');
        $this->assertTrue($sql->select()->where->hasNest());
        $this->assertInstanceOf('Pop\Db\Sql\Predicate', $sql->select()->where->getNest(0));
        $this->assertEquals('SELECT * FROM "ph_users" WHERE (("active" != 0) AND ("verified" = 1)) AND ("id" > 0)', $sql->render(true));
    }

    public function testInsert()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->insert([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ]);

        $s = $sql->render(true);
        $this->assertEquals('INSERT INTO "ph_users" ("username", "email") VALUES (\'testuser\', \'test@test.com\')', $s);
        $this->assertEquals('INSERT INTO "ph_users" ("username", "email") VALUES (\'testuser\', \'test@test.com\')', $sql->getSql());

    }

    public function testInsertException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->insert(null);
    }

    public function testUpdate()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->update([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ])->where(['id' => 1])->orderBy('id', 'ASC')->limit(1);

        $s = $sql->render(true);
        $this->assertEquals('UPDATE "ph_users" SET "username" = \'testuser\', "email" = \'test@test.com\' WHERE ("id" = 1) ORDER BY "id" ASC LIMIT 1', $s);
        $this->assertEquals('UPDATE "ph_users" SET "username" = \'testuser\', "email" = \'test@test.com\' WHERE ("id" = 1) ORDER BY "id" ASC LIMIT 1', $sql->getSql());
    }

    public function testUpdateException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->update(null);
    }

    public function testDelete()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $sql->delete()->where(['id' => 1])->orderBy('id', 'ASC')->limit(1);

        ob_start();
        $sql->render();
        $s = ob_get_clean();

        $this->assertEquals('DELETE FROM "ph_users" WHERE ("id" = 1) ORDER BY "id" ASC LIMIT 1', $s);
        $this->assertEquals('DELETE FROM "ph_users" WHERE ("id" = 1) ORDER BY "id" ASC LIMIT 1', $sql->getSql());

        if (file_exists(__DIR__  . '/tmp/db.sqlite')) {
            unlink(__DIR__  . '/tmp/db.sqlite');
        }
    }

}
