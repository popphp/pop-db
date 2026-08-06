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
        $predicateSet = @Condition::parse(['username' => 'testuser'], $sql);

        $this->assertEquals('(`username` = ?)', $predicateSet->render());
        $this->assertContains('testuser', $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testWrongArityThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['logins' => ['>=', 18, 21]], $sql);
    }

    public function testArrayShapedLegacyValueNowHandledAsInClause()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['role' => ['admin', 'editor']], $sql);

        // Should now properly treat array values as IN clauses (legacy behavior)
        $this->assertEquals('(`role` IN (?, ?))', $predicateSet->render());
        $params = $predicateSet->getParameters();
        $this->assertContains('admin', $params);
        $this->assertContains('editor', $params);
        $this->assertEquals(2, count($params));
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

    public function testLegacySuffixSyntaxStillWorks()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['logins>=' => 18], $sql);

        $this->assertEquals('(`logins` >= ?)', $predicateSet->render());
        $this->assertContains(18, $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testLegacySyntaxTriggersDeprecation()
    {
        $sql = $this->db->createSql();
        $deprecationTriggered = false;
        set_error_handler(function ($errno, $errstr) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
            return true; // mark as handled
        });

        try {
            Condition::parse(['logins>=' => 18], $sql);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($deprecationTriggered, 'Expected a deprecation notice for legacy syntax.');
        $this->db->disconnect();
    }

    public function testNewSyntaxDoesNotTriggerDeprecation()
    {
        $sql = $this->db->createSql();
        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $predicateSet = Condition::parse(['logins' => ['>=', 18]], $sql);

        restore_error_handler();
        $this->assertFalse($deprecationTriggered, 'Did not expect a deprecation notice for pure new-syntax input.');
        $this->assertEquals('(`logins` >= ?)', $predicateSet->render());
        $this->db->disconnect();
    }

    public function testMixedLegacyAndNewSyntaxInSameCall()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['username>' => 'a', 'logins' => ['>=', 18]], $sql);

        $this->assertEquals(2, count($predicateSet->getPredicates()));
        $params = $predicateSet->getParameters();
        $this->assertContains('a', $params);
        $this->assertContains(18, $params);
        $this->db->disconnect();
    }

    public function testLegacyMultipleConditionsSameColumnNoCollision()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['logins>=' => 18, 'logins<=' => 65], $sql);

        // Both conditions should be preserved (regression test for collision bug)
        $this->assertEquals(2, count($predicateSet->getPredicates()));
        $render = $predicateSet->render();
        $this->assertStringContainsString('>=', $render);
        $this->assertStringContainsString('<=', $render);
        $params = $predicateSet->getParameters();
        $this->assertContains(18, $params);
        $this->assertContains(65, $params);
        $this->db->disconnect();
    }

    public function testLegacyWithPostgresPlaceholders()
    {
        // Test PostgreSQL $ placeholder handling
        if (!extension_loaded('pgsql')) {
            $this->markTestSkipped('PostgreSQL extension not loaded');
        }

        try {
            $db = Db::pgsqlConnect([
                'database' => $_ENV['PGSQL_DB'] ?? 'test_popdb',
                'username' => $_ENV['PGSQL_USER'] ?? 'postgres',
                'password' => $_ENV['PGSQL_PASS'] ?? 'postgres',
                'host'     => $_ENV['PGSQL_HOST'] ?? '127.0.0.1'
            ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to PostgreSQL: ' . $e->getMessage());
        }

        $sql          = $db->createSql();
        $predicateSet = @Condition::parse(['logins>=' => 18], $sql);

        // PostgreSQL uses double quotes and $1 placeholder (not backticks like MySQL)
        $this->assertEquals('("logins" >= $1)', $predicateSet->render());
        $this->assertContains(18, $predicateSet->getParameters());
        $db->disconnect();
    }

}
