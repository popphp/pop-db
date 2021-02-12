<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class TableMysqlJoinTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('users');
        $schema->execute();

        $schema->dropIfExists('user_info');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        $schema->create('user_info')
            ->int('user_id', 16)
            ->varchar('notes', 255)
            ->foreignKey('user_id')->references('users')->on('id')->onDelete('CASCADE');

        $schema->execute();

        \Pop\Db\Test\TestAsset\Users::setDb($this->db);
        \Pop\Db\Test\TestAsset\UserInfo::setDb($this->db);
    }

    public function testSelectJoin1()
    {
        $table1 = new Gateway\Table('users');
        $table1->insert([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);

        $table2 = new Gateway\Table('user_info');
        $table2->insert([
            'user_id' => '1',
            'notes'   => 'Test notes'
        ]);

        $table = new Gateway\Table('users');

        $table->select(['*'], null, null, [
            'join' => [
                [
                    'type'    => 'leftJoin',
                    'table'   => 'user_info',
                    'columns' => [
                        'users.id' => 'user_info.user_id'
                    ]
                ]
            ]
        ]);

        $this->assertTrue($table->hasRows());
        $rows = $table->getRows();
        $this->assertEquals('testuser1', $rows[0]['username']);
        $this->assertEquals('Test notes', $rows[0]['notes']);
        $this->db->disconnect();
    }

    public function testSelectJoin2()
    {
        $table1 = new Gateway\Table('users');
        $table1->insert([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);

        $table2 = new Gateway\Table('user_info');
        $table2->insert([
            'user_id' => '1',
            'notes'   => 'Test notes'
        ]);

        $table = new Gateway\Table('users');

        $table->select(['*'], null, null, [
            'join' => [
                [
                    'table'   => 'user_info',
                    'columns' => [
                        'users.id' => 'user_info.user_id'
                    ]
                ]
            ]
        ]);

        $this->assertTrue($table->hasRows());
        $rows = $table->getRows();
        $this->assertEquals('testuser1', $rows[0]['username']);
        $this->assertEquals('Test notes', $rows[0]['notes']);
        $this->db->disconnect();
    }

    public function tearDown(): void
    {
        $this->db->connect();
        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();
        $schema->drop('user_info');
        $schema->execute();

        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();
        $schema->drop('users');
        $schema->execute();

        $this->db->disconnect();
    }

}
