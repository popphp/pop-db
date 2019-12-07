<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class TablePgsqlTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql')),
            'host'     => 'localhost'
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('pg_users');
        $schema->execute();

        $schema->create('pg_users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\PgUsers::setDb($this->db);
    }

    public function testInsert()
    {
        $table = new Gateway\Table('pg_users');
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
        $table = new Gateway\Table('pg_users');
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
        $table = new Gateway\Table('pg_users');
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
        $table = new Gateway\Table('pg_users');
        $tableInfo = $table->getTableInfo();
        $this->assertTrue(is_array($tableInfo));

        $schema = $this->db->createSchema();
        $schema->drop('pg_users');
        $schema->execute();

        $this->db->disconnect();
    }

}
