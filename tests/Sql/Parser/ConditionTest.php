<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Db;
use Pop\Db\Sql\Parser\Condition;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
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

    public function testEqualTo()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['email' => ['=', 'test@test.com']], $sql);

        $this->assertEquals('(`email` = ?)', $predicateSet->render());
        $this->assertEquals(['email' => 'test@test.com'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testGreaterThanOrEqualTo()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['logins' => ['>=', 18]], $sql);

        $this->assertEquals('(`logins` >= ?)', $predicateSet->render());
        $this->assertEquals(['logins' => 18], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testLikeAcceptsLowercaseOperator()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => ['like', '%smith%']], $sql);

        $this->assertEquals('(`username` LIKE ?)', $predicateSet->render());
        $this->assertEquals(['username' => '%smith%'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testPlainScalarStillMeansEquals()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => 'testuser'], $sql);

        $this->assertEquals('(`username` = ?)', $predicateSet->render());
        $this->assertEquals(['username' => 'testuser'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testWrongArityThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['logins' => ['>=', 18, 21]], $sql);
    }

}
