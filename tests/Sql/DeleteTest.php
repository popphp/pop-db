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
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => '127.0.0.1'
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
