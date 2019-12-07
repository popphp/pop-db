<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Record;
use Pop\Db\Test\TestAsset\MockData;
use Pop\Db\Test\TestAsset\Users;
use PHPUnit\Framework\TestCase;

class RecordTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => 'localhost'
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('users');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\Users::setDb($this->db);
    }

    public function testConstructor()
    {
        $user = new Users([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $user);
        $this->assertInstanceOf('Pop\Db\Record', $user);
        $this->db->disconnect();
    }

    public function testConstructorTable()
    {
        $user = new Users('users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testConstructorDb()
    {
        $user = new Users($this->db);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Users::getDb());
        $this->db->disconnect();
    }

    public function testHasDb()
    {
        $user = new Users($this->db);
        $this->assertTrue(Users::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Users::db());
        $this->db->disconnect();
    }

    public function testSetRecordDb()
    {
        Record::setDb($this->db);
        $this->assertTrue(Record::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Record::db());
        $this->db->disconnect();
    }

    public function testSetDefaultDb()
    {
        Record::setDefaultDb($this->db);
        $this->assertTrue(Record::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Record::db());
        $this->db->disconnect();
    }

    public function testGetSql()
    {
        $this->assertInstanceOf('Pop\Db\Sql', Users::getSql());
        $this->assertInstanceOf('Pop\Db\Sql', Users::sql());
        $this->db->disconnect();
    }

    public function testTable()
    {
        $this->assertEquals('users', Users::table());
        $this->db->disconnect();
    }

    public function testSetTableWithClassName()
    {
        $user = new Users();
        $user->setTableFromClassName('Users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetTableWithUnderscore()
    {
        $user = new Users();
        $user->setTableFromClassName('MyApp_Users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetTable()
    {
        $user = new Users();
        $user->setTableFromClassName();
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetPrefix()
    {
        $user = new Users();
        $user->setPrefix('prefix_');
        $this->assertEquals('prefix_', $user->getPrefix());
        $this->assertEquals('prefix_users', $user->getFullTable());
        $user->setPrefix(null);
        $this->db->disconnect();
    }

    public function testSetPrimaryKeys()
    {
        $user = new Users();
        $user->setPrimaryKeys(['id']);
        $this->assertTrue((['id'] == $user->getPrimaryKeys()));
        $this->assertTrue(is_array($user->getPrimaryValues()));
        $this->db->disconnect();
    }

    public function testGetGateways()
    {
        $user = new Users();
        $this->assertInstanceOf('Pop\Db\Gateway\Row', $user->getRowGateway());
        $this->assertInstanceOf('Pop\Db\Gateway\Table', $user->getTableGateway());
        $this->assertTrue(is_array($user->toArray()));
        $this->assertEquals(0, $user->count());
        $this->assertEquals(0, count($user->rows()));
        $this->assertEquals(0, $user->countRows());
        $this->assertFalse($user->hasRows());

        $i = 0;
        foreach ($user as $u) {
            $i++;
        }
        $this->assertEquals(0, $i);
        $this->db->disconnect();
    }

    public function testSetColumnsArrayAccess()
    {
        $data = new MockData([
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $user = new Users();
        $user->setColumns($data);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('password1', $ary['password']);
        $this->db->disconnect();
    }

    public function testSetColumnsRecord()
    {
        $data = new Users([
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $user = new Users();
        $user->setColumns($data);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('password1', $ary['password']);
        $this->db->disconnect();
    }

    public function testSetColumnsException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new Users();
        $user->setColumns('bad');
    }

    public function testSetRows()
    {
        $user = new Users();
        $user->setRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ]);
        $this->assertEquals(2, $user->countRows());
        $this->db->disconnect();
    }

    public function testProcessRows()
    {
        $user = new Users();
        $rows = $user->processRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ]);
        $this->assertEquals(2, count($rows));
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $rows[0]);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $rows[1]);
        $this->db->disconnect();
    }

    public function testProcessRowsAsArray()
    {
        $user = new Users();
        $rows = $user->processRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ], true);
        $this->assertEquals(2, count($rows));
        $this->assertIsArray($rows[0]);
        $this->assertIsArray($rows[1]);
        $this->db->disconnect();
    }

    public function testSettersAndGetters()
    {
        $user = new Users();
        $user->username   = 'testuser1';
        $user['password'] = 'password1';
        $this->assertTrue(isset($user->username));
        $this->assertTrue(isset($user['password']));
        $this->assertEquals('testuser1', $user->username);
        $this->assertEquals('password1', $user['password']);
        unset($user->username);
        unset($user['password']);
        $this->assertFalse(isset($user->username));
        $this->assertFalse(isset($user['password']));
    }

}