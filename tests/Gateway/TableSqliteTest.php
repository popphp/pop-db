<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class TableSqliteTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);

        $this->db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('sq_users');
        $schema->execute();

        $schema->create('sq_users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\SqUsers::setDb($this->db);
    }

    public function testInsert()
    {
        $table = new Gateway\Table('sq_users');
        $this->assertFalse($table->hasRows());
        $table->insert([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);

        $table->select(null, "username = testuser1");
        $this->assertTrue($table->hasRows());
        $this->assertEquals(1, $table->count());
        $this->db->disconnect();
    }

    public function testInsertRows()
    {
        $table = new Gateway\Table('sq_users');
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

        $table->select(null, "username = testuser2");
        $this->assertTrue($table->hasRows());
        $this->assertEquals(1, $table->count());
        $this->db->disconnect();
    }

    public function testUpdate()
    {
        $table = new Gateway\Table('sq_users');
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

    public function testGetTableInfo()
    {
        $table = new Gateway\Table('sq_users');
        $tableInfo = $table->getTableInfo();
        $this->assertTrue(is_array($tableInfo));

        $schema = $this->db->createSchema();
        $schema->drop('sq_users');
        $schema->execute();

        $this->db->disconnect();
        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

}
