<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => '127.0.0.1'
        ]);
    }

    public function testSet()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Where', $sql->update()->where);
        $sql->update('users')->set('username', 'admin')->where('id = 1');
        $this->assertEquals('admin', $sql->update()->getValue('username'));
        $this->assertEquals("UPDATE `users` SET `username` = 'admin' WHERE (`id` = 1)", (string)$sql->update());
    }

    public function testGetException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $sql = $this->db->createSql();
        $var = $sql->update()->bad;
    }

    public function testRenderWithNamedValues()
    {
        $sql = $this->db->createSql();
        $sql->update('users')->set('username', ':username')->where('id = ?');
        $this->assertEquals("UPDATE `users` SET `username` = ? WHERE (`id` = ?)", (string)$sql->update());

        $this->db->disconnect();
    }

    public function testRenderPgsqlWithNamedValues()
    {
        $db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql'))
        ]);
        $sql = $db->createSql();
        $sql->update('users')->set('username', ':username')->where('id = $2');
        $this->assertEquals('UPDATE "users" SET "username" = $1 WHERE ("id" = $2)', (string)$sql->update());
    }

}
