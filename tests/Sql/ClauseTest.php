<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class ClauseTest extends TestCase
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

    public function testAlias()
    {
        $sql = $this->db->createSql();
        $sql->select()->setAlias('users');
        $this->assertTrue($sql->select()->hasAlias());
        $this->assertEquals('users', $sql->select()->getAlias());
        $this->db->disconnect();
    }

    public function testWhereAnd()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ? AND email = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) AND (`email` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testWhereOr()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ? OR email = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) OR (`email` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testWhereArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where(['id = ?', 'email = ?']);
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) AND (`email` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testAndWhere()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ?')->andWhere('email = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) AND (`email` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testOrWhere()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ?')->orWhere('email = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) OR (`email` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testAndWhereArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ?')->andWhere(['email = ?', 'username = ?']);
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) AND (`email` = ?) AND (`username` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testOrWhereArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->where('id = ?')->orWhere(['email = ?', 'username = ?']);
        $this->assertEquals("SELECT * FROM `users` WHERE ((`id` = ?) OR (`email` = ?) OR (`username` = ?))", $sql->render());
        $this->db->disconnect();
    }

    public function testInitAndWhere()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->andWhere('id = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE (`id` = ?)", $sql->render());
        $this->db->disconnect();
    }

    public function testInitOrWhere()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orWhere('id = ?');
        $this->assertEquals("SELECT * FROM `users` WHERE (`id` = ?)", $sql->render());

        $this->db->disconnect();
    }

}