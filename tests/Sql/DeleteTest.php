<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
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

    public function testRender()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Where', $sql->delete()->where);
        $sql->delete()->from('users')->where('id = 1');
        $this->assertEquals("DELETE FROM `users` WHERE (`id` = 1)", (string)$sql->delete());
    }

    public function testGetException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $sql = $this->db->createSql();
        $var = $sql->delete()->bad;

        $this->db->disconnect();
    }

}
