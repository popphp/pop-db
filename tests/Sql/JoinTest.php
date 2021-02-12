<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Join;
use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
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

    public function testGetters()
    {
        $join = new Join($this->db->createSql(), 'user_info', ['user_info.user_id' => 'users.id'], 'LEFT JOIN');
        $this->assertEquals('`user_info`', $join->getForeignTable());
        $this->assertEquals('users.id', $join->getColumns()['user_info.user_id']);
        $this->assertEquals('LEFT JOIN', $join->getJoin());
    }

    public function testForeignTableAlias()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->leftJoin(['userinfo' => 'user_info'], ['userinfo.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT JOIN `user_info` AS `userinfo` ON (`userinfo`.`user_id` = `users`.`id`)', $sql->render());
    }

    public function testForeignTableSql()
    {
        $sql1 = $this->db->createSql();
        $sql2 = $this->db->createSql();
        $sql2->select('phone')->from('user_info')->setAlias('userinfo');
        $sql1->select()->from('users')->leftJoin($sql2, ['userinfo.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT JOIN (SELECT `phone` FROM `user_info`) AS `userinfo` ON (`userinfo`.`user_id` = `users`.`id`)', $sql1->render());
    }

    public function testColumnsArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->leftJoin('user_info', ['user_info.user_id' => ['users.id', 'users.foo']]);
        $this->assertEquals('SELECT * FROM `users` LEFT JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id` AND `user_info`.`user_id` = `users`.`foo`)', $sql->render());
        $this->db->disconnect();
    }

}