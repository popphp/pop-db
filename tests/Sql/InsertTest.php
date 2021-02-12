<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
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
        $this->db->disconnect();
    }

    public function testRenderPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql = $db->createSql();
        $sql->insert()->into('users')->values(['username' => ':username']);
        $this->assertEquals('INSERT INTO "users" ("username") VALUES ($1)', (string)$sql->insert());
    }

    public function testConflictPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql = $db->createSql();
        $sql->insert()->into('users')->values(['username' => 'admin'])->onConflict(['username'], 'id');
        $this->assertEquals('INSERT INTO "users" ("username") VALUES (\'admin\') ON CONFLICT ("id") DO UPDATE SET "username" = excluded.username', (string)$sql->insert());
    }

}
