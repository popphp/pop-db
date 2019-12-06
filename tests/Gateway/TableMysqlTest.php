<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class TableMysqlTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
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
        $table = new Gateway\Table('users');
        $this->assertInstanceOf('Pop\Db\Gateway\Table', $table);
        $this->assertEquals('users', $table->getTable());
        $this->db->disconnect();
    }

    public function testSetRows()
    {
        $table = new Gateway\Table('users');
        $table->setRows([
            ['username' => 'admin1'],
            ['username' => 'admin2']
        ]);
        $this->assertEquals(2, $table->getNumberOfRows());
        $this->assertEquals(2, $table->count());

        $this->db->disconnect();
    }

    public function testGetRows()
    {
        $table = new Gateway\Table('users');
        $this->assertTrue(is_array($table->getRows()));
        $this->assertTrue(is_array($table->rows()));
        $this->assertTrue(is_array($table->toArray()));
        $this->assertEquals(0, $table->getNumberOfRows());
        $this->assertEquals(0, $table->count());

        $i = 0;
        foreach ($table as $row) {
            $i++;
        }

        $this->assertEquals(0, $i);

        $this->db->disconnect();
    }

    public function testInsertAndSelect()
    {
        $table = new Gateway\Table('users');
        $this->assertFalse($table->hasRows());
        $table->insert([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);

        $table->select(null, "username = testuser1");
        $this->assertTrue($table->hasRows());
        $this->assertEquals(1, $table->count());

        $table->insertRows([
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ]
        ]);

        $table->select();
        $this->assertTrue($table->hasRows());
        $this->assertEquals(3, $table->count());
        $this->db->disconnect();
    }

    public function testUpdate()
    {
        $table = new Gateway\Table('users');
        $table->insertRows([
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ],
            [
                'username' => 'testuser6',
                'password' => 'password6',
                'email'    => 'testuser6@test.com'
            ]
        ]);

        $table->update(['password' => '123456', 'email' => 'testuser123456@test.com'], "username = 'testuser6'");

        $table->select(null, "username = 'testuser6'");
        $rows = $table->getRows();
        $this->assertEquals('123456', $rows[0]['password']);
        $this->assertEquals('testuser123456@test.com', $rows[0]['email']);
    }

    public function testDelete()
    {
        $table = new Gateway\Table('users');
        $table->insert([
            'username' => 'testuser7',
            'password' => 'password7',
            'email'    => 'testuser7@test.com'
        ]);

        $table->delete("username = 'testuser7'");

        $table->select(null, "username = 'testuser7'");
        $this->assertFalse($table->hasRows());
    }

    public function testDeleteWithParameters()
    {
        $table = new Gateway\Table('users');
        $table->insert([
            'username' => 'testuser8',
            'password' => 'password8',
            'email'    => 'testuser8@test.com'
        ]);

        $table->delete("username = ?", ['username' => 'testuser8']);

        $table->select(null, "username = 'testuser8'");
        $this->assertFalse($table->hasRows());
    }

    public function testSelectWithParameters()
    {
        $table = new Gateway\Table('users');
        $table->insert([
            'username' => 'testuser9',
            'password' => 'password9',
            'email'    => 'testuser9@test.com'
        ]);

        $table->select(null, "username = ?", ['username' => 'testuser9'], ['offset' => 0, 'limit' => 1, 'order' => 'username ASC']);
        $this->assertTrue($table->hasRows());
        $rows = $table->getRows();
        $this->assertEquals('testuser9', $rows[0]['username']);
    }

    public function testSelectOrderArray()
    {
        $table = new Gateway\Table('users');
        $table->insert([
            'username' => 'testuser9',
            'password' => 'password9',
            'email'    => 'testuser9@test.com'
        ]);

        $table->select(null, "username = ?", ['username' => 'testuser9'], ['offset' => 0, 'limit' => 1, 'order' => ['username ASC']]);
        $this->assertTrue($table->hasRows());
        $rows = $table->getRows();
        $this->assertEquals('testuser9', $rows[0]['username']);
    }

    public function testGetTableInfo()
    {
        $table = new Gateway\Table('users');
        $tableInfo = $table->getTableInfo();
        $this->assertTrue(is_array($tableInfo));

        $schema = $this->db->createSchema();
        $schema->drop('users');
        $schema->execute();

        $this->db->disconnect();
    }

}
