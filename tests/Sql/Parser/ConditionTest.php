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

    public function testArrayShapedLegacyValueIgnoredPendingTask8()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['role' => ['admin', 'editor']], $sql);

        // Should safely produce empty predicate set (no error, no predicates)
        $this->assertEquals('', $predicateSet->render());
        $this->assertEquals([], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testIn()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => ['IN', ['admin', 'editor']]], $sql);

        $this->assertEquals('(`username` IN (?, ?))', $predicateSet->render());
        $this->assertEquals(['username_1' => 'admin', 'username_2' => 'editor'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testNotIn()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => ['NOT IN', ['admin', 'editor']]], $sql);

        $this->assertEquals('(`username` NOT IN (?, ?))', $predicateSet->render());
        $this->db->disconnect();
    }

    public function testInRequiresArrayValue()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['username' => ['IN', 'admin']], $sql);
    }

    public function testBetween()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['logins' => ['BETWEEN', 5, 10]], $sql);

        $this->assertEquals('(`logins` BETWEEN ? AND ?)', $predicateSet->render());
        $this->assertEquals(['logins_1' => 5, 'logins_2' => 10], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testBetweenWithCommaContainingValueDoesNotBreak()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['amount' => ['BETWEEN', '1,000', '2,000']], $sql);

        $this->assertEquals('(`amount` BETWEEN ? AND ?)', $predicateSet->render());
        $this->assertEquals(['amount_1' => '1,000', 'amount_2' => '2,000'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testNotBetweenWrongArityThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['logins' => ['NOT BETWEEN', 5]], $sql);
    }

    public function testIsNull()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['deleted_at' => ['IS NULL']], $sql);

        $this->assertEquals('(`deleted_at` IS NULL)', $predicateSet->render());
        $this->assertFalse($predicateSet->hasParameters());
        $this->db->disconnect();
    }

    public function testIsNotNull()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['deleted_at' => ['IS NOT NULL']], $sql);

        $this->assertEquals('(`deleted_at` IS NOT NULL)', $predicateSet->render());
        $this->db->disconnect();
    }

    public function testColumnEndingInReservedSuffixCharacterWorksViaNewSyntax()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['discount-' => ['=', 5]], $sql);

        $this->assertEquals('(`discount-` = ?)', $predicateSet->render());
        $this->db->disconnect();
    }

}
