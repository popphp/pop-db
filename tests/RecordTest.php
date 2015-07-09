<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Record;

class RecordTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $user = new TestAsset\Users();
    }

    public function testSetDbException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $db = TestAsset\Users::getDb();
    }

    public function testSetDb()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        Record::setDb($db, true);
        $this->assertTrue(TestAsset\Users::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', TestAsset\Users::getDb());
    }

    public function testSetDbChildClass()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        TestAsset\Users::setDb($db, true);
        $this->assertTrue(TestAsset\Users::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', TestAsset\Users::db());
        $this->assertInstanceOf('Pop\Db\Sql', TestAsset\Users::getSql());
        $this->assertInstanceOf('Pop\Db\Sql', TestAsset\Users::sql());
    }

    public function testConstructorSetDb()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $user = new TestAsset\Users(null, $db);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', TestAsset\Users::db());
    }

    public function testGetPrefix()
    {
        $this->assertEquals('ph_', TestAsset\Users::getPrefix());
    }

    public function testGetTable()
    {
        $this->assertEquals('ph_users', TestAsset\Users::getTable());
    }

    public function testGetTableInfo()
    {
        $info = TestAsset\Users::getTableInfo();
        $this->assertEquals('ph_users', $info['tableName']);
        $this->assertEquals('id', $info['primaryId'][0]);
        $this->assertEquals(6, count($info['columns']));
    }

    public function testGetPrimaryKeys()
    {
        $user = new TestAsset\Users();
        $this->assertEquals('id', $user->getPrimaryKeys()[0]);
    }

    public function testSetColumnsException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $user = new TestAsset\Users();
        $user->setColumns('bad');
    }

    public function testSaveFindAndDelete()
    {
        $user = new TestAsset\Users([
            'username' => 'testuser',
            'password' => '12test34',
            'email'    => 'test@test.com',
            'active'   => 1,
            'verified' => 1
        ]);

        $user->save();
        $this->assertNotNull($user->id);
        $this->assertTrue(isset($user->id));
        $this->assertTrue(isset($user['id']));
        $id = $user->id;
        $id = $user['id'];

        $u = TestAsset\Users::findById($id);
        $this->assertEquals('testuser', $u->username);
        $u->username   = 'testuser1';
        $u['username'] = 'testuser1';
        $u->save();

        unset($u->id);
        unset($u['id']);

        $u = TestAsset\Users::findBy(['username' => 'testuser1']);
        $this->assertEquals($id, $u->id);
        $this->assertEquals(6, count($u->getColumns()));
        $this->assertEquals(6, count($u->getColumnsAsObject()));

        $this->assertEquals(1, count($u->rows()));
        $this->assertEquals(1, count($u->getRows()));
        $this->assertEquals(1, count($u->getRowObjects()));

        $users = TestAsset\Users::findAll();
        $this->assertEquals(1, $users->count());

        $this->assertEquals(1, TestAsset\Users::getTotal(['id >=' => 0]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id >' => 0]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id <=' => 10000]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id <' => 10000]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id !=' => 10000]));
        $this->assertEquals(0, TestAsset\Users::getTotal(['username' => null]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['username-' => null]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id' => [1001, 1002]]));
        $this->assertEquals(0, TestAsset\Users::getTotal(['id-' => [1001, 1002]]));
        $this->assertEquals(1, TestAsset\Users::getTotal(['id' => '(1000, 1002)']));
        $this->assertEquals(0, TestAsset\Users::getTotal(['id-' => '(1000, 1002)']));
        $this->assertEquals(1, TestAsset\Users::getTotal(['username' => 'test%']));
        $this->assertEquals(0, TestAsset\Users::getTotal(['username' => 'test%-']));
        $this->assertEquals(1, TestAsset\Users::getTotal(['username' => '%user1']));
        $this->assertEquals(0, TestAsset\Users::getTotal(['username' => '-%user1']));
        $this->assertEquals(1, TestAsset\Users::getTotal(['username' => 'testuser1']));

        $u->delete();
        $u->setColumns(new \ArrayObject([
            'username' => 'testuser',
            'password' => '12test34',
            'email'    => 'test@test.com',
            'active'   => 1,
            'verified' => 1
        ], \ArrayObject::ARRAY_AS_PROPS));
        $u->setColumns(null);
        $u->setRows(null);

        $u->delete(['username' => 'testuser']);

        $users = TestAsset\Users::findAll();
        $this->assertEquals(0, $users->count());
    }

    public function testExecute()
    {
        $params = [
            'username' => 'testuser',
            'password' => '12test34',
            'email'    => 'test@test.com',
            'active'   => 1,
            'verified' => 1
        ];
        TestAsset\Users::execute(
            'INSERT INTO "ph_users" ("username", "password", "email", "active", "verified") VALUES (:username, :password, :email, :active, :verified)', $params
        );

        TestAsset\Users::execute(
            'INSERT INTO "ph_users" ("username") VALUES (:username)', 'testuser2'
        );

        $users = TestAsset\Users::execute('SELECT * FROM "ph_users" WHERE "username" = :username', ['username' => 'testuser']);
        $this->assertEquals(1, $users->count());
    }

    public function testQuery()
    {
        $users = TestAsset\Users::query('SELECT * FROM ph_users');
        $this->assertEquals(1, $users->count());
    }

}