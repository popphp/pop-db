<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
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

    public function testDistinct()
    {
        $sql = $this->db->createSql();
        $sql->select(['username'])->distinct()->from('users');
        $this->assertEquals('SELECT DISTINCT `username` FROM `users`', (string)$sql);
        $this->db->disconnect();
    }

    public function testAlias()
    {
        $sql = $this->db->createSql();
        $sql->select(['username'])->from('users');
        $sql->select()->asAlias('test_table');
        $this->assertEquals('(SELECT `username` FROM `users`) AS `test_table`', (string)$sql);
        $this->db->disconnect();
    }

    public function testJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->join('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->outerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` OUTER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT OUTER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT OUTER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL OUTER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->innerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` INNER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT INNER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT INNER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL INNER JOIN `user_info` ON (`user_info`.`user_id` = `users`.`id`)', (string)$sql);
        $this->db->disconnect();
    }

    public function testMagicException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $sql = $this->db->createSql();
        $bad = $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->bad;
    }

    public function testWhereMagic()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Where', $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->where);
        $this->db->disconnect();
    }

    public function testHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING (`total` > 1)', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingMagic()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Having', $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having);
        $this->db->disconnect();
    }

    public function testAndHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->andHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->orHaving('total = 0');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` = 0))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingAnd()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1 AND total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingAndHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->andHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingOr()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1 OR total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingOrHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->orHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testAndHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->andHaving(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->orHaving(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupBy()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'total' => 'COUNT(1)'])->from('users')->groupBy('username');
        $this->assertEquals('SELECT `username`, COUNT(1) AS `total` FROM `users` GROUP BY `username`', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupByArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'email', 'total' => 'COUNT(1)'])->from('users')->groupBy(['username', 'email']);
        $this->assertEquals('SELECT `username`, `email`, COUNT(1) AS `total` FROM `users` GROUP BY `username`, `email`', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupByString()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'email', 'total' => 'COUNT(1)'])->from('users')->groupBy('username, email');
        $this->assertEquals('SELECT `username`, `email`, COUNT(1) AS `total` FROM `users` GROUP BY `username`, `email`', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderBy()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username', 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy(['username', 'email'], 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username`, `email` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByString()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username, email', 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username`, `email` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByRandom()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username', 'RAND');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username` RAND()', (string)$sql);
        $this->db->disconnect();
    }

    public function testLimit()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->limit(1);
        $this->assertEquals('SELECT * FROM `users` LIMIT 1', (string)$sql);
        $this->db->disconnect();
    }

    public function testOffset()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->offset(25);
        $this->assertEquals('SELECT * FROM `users` OFFSET 25', (string)$sql->select());
        $this->db->disconnect();
    }

    public function testNestedSql()
    {
        $sql1 = $this->db->createSql();
        $sql2 = $this->db->createSql();
        $sql2->select('username')->from('users');
        $sql2->select()->setAlias('usernames');
        $sql1->select()->from($sql2);
        $this->assertEquals('SELECT * FROM (SELECT `username` FROM `users`) AS `usernames`', $sql1->render());
        $this->db->disconnect();
    }

    public function testNestedSelect()
    {
        $sql1 = $this->db->createSql();
        $sql2 = $this->db->createSql();
        $sql2->select('username')->from('users');
        $sql2->select()->setAlias('usernames');
        $sql1->select()->from($sql2->select());
        $this->assertEquals('SELECT * FROM (SELECT `username` FROM `users`) AS `usernames`', $sql1->render());

        $this->db->disconnect();
    }


}
