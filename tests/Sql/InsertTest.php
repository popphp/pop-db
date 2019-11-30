<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
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
    }

    public function testInsert()
    {
        $sql = $this->db->createSql();
        $sql->insert()->into('users')->values(['username' => 'admin']);
        $this->assertEquals("INSERT INTO `users` (`username`) VALUES ('admin')", (string)$sql->insert());
    }

    public function testUpdateOnDuplicateKey()
    {
        $sql = $this->db->createSql();
        $sql->insert()->into('users')->values(['username' => 'admin'])->onDuplicateKeyUpdate(['username']);
        $this->assertEquals("INSERT INTO `users` (`username`) VALUES ('admin') ON DUPLICATE KEY UPDATE `username` = VALUES(username)", (string)$sql->insert());
    }

    public function testRenderPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql'))
        ]);
        $sql = $db->createSql();
        $sql->insert()->into('users')->values(['username' => ':username']);
        $this->assertEquals('INSERT INTO "users" ("username") VALUES ($1)', (string)$sql->insert());
    }

    public function testConflictPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql'))
        ]);
        $sql = $db->createSql();
        $sql->insert()->into('users')->values(['username' => 'admin'])->onConflict(['username'], 'id');
        $this->assertEquals('INSERT INTO "users" ("username") VALUES (\'admin\') ON CONFLICT ("id") DO UPDATE SET "username" = excluded.username', (string)$sql->insert());
    }

}
