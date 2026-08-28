<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Db;
use Pop\Db\Sql\Parser\Condition;
use Pop\Db\Sql\Parser\Expression;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function tearDownAfterClass(): void
    {
        foreach (['mysql', 'pgsql'] as $dialect) {
            try {
                $db     = self::connect($dialect);
                $schema = $db->createSchema();
                $schema->dropIfExists('cond_users');
                $schema->execute();
                $db->disconnect();
            } catch (\Exception $e) {
                // Nothing to clean up
            }
        }

        $file = __DIR__ . '/../../tmp/condition.sqlite';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /*
     * ---------------------------------------------------------------------------------------
     * Multi-dialect coverage
     *
     * Every operator is exercised against MySQL ('?'), PostgreSQL ('$n') and SQLite (':col'),
     * asserting the rendered SQL, the bound parameters AND that the statement actually
     * prepares, binds and executes against the live database, returning the correct rows.
     * ---------------------------------------------------------------------------------------
     */

    public static function dialects(): array
    {
        return [
            'mysql'  => ['mysql'],
            'pgsql'  => ['pgsql'],
            'sqlite' => ['sqlite'],
        ];
    }

    #[DataProvider('dialects')]
    public function testEqualToAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['=', 'admin']],
            '(%c%username%c% = %p1%)', ['1_username' => 'admin'], ['admin']
        );
    }

    #[DataProvider('dialects')]
    public function testNotEqualToAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['!=', 'admin']],
            '(%c%username%c% != %p1%)', ['1_username' => 'admin'], ['editor', 'ghost', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testGreaterThanAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['>', 30]],
            '(%c%logins%c% > %p1%)', ['1_logins' => 30], ['admin']
        );
    }

    #[DataProvider('dialects')]
    public function testGreaterThanOrEqualToAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['>=', 30]],
            '(%c%logins%c% >= %p1%)', ['1_logins' => 30], ['admin', 'editor']
        );
    }

    #[DataProvider('dialects')]
    public function testLessThanAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['<', 30]],
            '(%c%logins%c% < %p1%)', ['1_logins' => 30], ['ghost', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testLessThanOrEqualToAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['<=', 30]],
            '(%c%logins%c% <= %p1%)', ['1_logins' => 30], ['editor', 'ghost', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testLikeAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['LIKE', '%dmi%']],
            '(%c%username%c% LIKE %p1%)', ['1_username' => '%dmi%'], ['admin']
        );
    }

    #[DataProvider('dialects')]
    public function testNotLikeAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['NOT LIKE', '%dmi%']],
            '(%c%username%c% NOT LIKE %p1%)', ['1_username' => '%dmi%'], ['editor', 'ghost', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testInAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['IN', ['admin', 'editor']]],
            '(%c%username%c% IN (%p1%, %p2%))', ['1_username' => 'admin', '2_username' => 'editor'],
            ['admin', 'editor']
        );
    }

    #[DataProvider('dialects')]
    public function testNotInAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => ['NOT IN', ['admin', 'editor']]],
            '(%c%username%c% NOT IN (%p1%, %p2%))', ['1_username' => 'admin', '2_username' => 'editor'],
            ['ghost', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testBetweenAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['BETWEEN', 1, 40]],
            '(%c%logins%c% BETWEEN %p1% AND %p2%)', ['1_logins' => 1, '2_logins' => 40], ['editor', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testNotBetweenAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['logins' => ['NOT BETWEEN', 1, 40]],
            '(%c%logins%c% NOT BETWEEN %p1% AND %p2%)', ['1_logins' => 1, '2_logins' => 40], ['admin', 'ghost']
        );
    }

    #[DataProvider('dialects')]
    public function testIsNullAcrossDialects(string $dialect)
    {
        $this->assertCondition($dialect, ['role' => ['IS NULL']], '(%c%role%c% IS NULL)', [], ['ghost']);
    }

    #[DataProvider('dialects')]
    public function testIsNotNullAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['role' => ['IS NOT NULL']], '(%c%role%c% IS NOT NULL)', [], ['admin', 'editor', 'viewer']
        );
    }

    #[DataProvider('dialects')]
    public function testPlainEqualityAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect, ['username' => 'admin'], '(%c%username%c% = %p1%)', ['1_username' => 'admin'], ['admin']
        );
    }

    #[DataProvider('dialects')]
    public function testBareNullMeansIsNullAcrossDialects(string $dialect)
    {
        $this->assertCondition($dialect, ['role' => null], '(%c%role%c% IS NULL)', [], ['ghost']);
    }

    /**
     * Regression: a column repeated across two OR branches must produce two distinct
     * parameter keys, otherwise PredicateSet::getParameters()'s array_merge() drops one of
     * them (ArgumentCountError on MySQL, silently wrong values on SQLite).
     */
    #[DataProvider('dialects')]
    public function testDuplicateColumnAcrossOrBranchesAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect,
            ['OR' => [['logins' => ['>=', 65]], ['logins' => ['<=', 5]]]],
            '((%c%logins%c% >= %p1%) OR (%c%logins%c% <= %p2%))',
            ['1_logins' => 65, '2_logins' => 5],
            ['admin', 'ghost', 'viewer']
        );
    }

    /**
     * Regression: the same column used at the top level AND inside a group.
     */
    #[DataProvider('dialects')]
    public function testDuplicateColumnBetweenTopLevelAndGroupAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect,
            ['logins' => ['>=', 1], 'OR' => [['logins' => ['>=', 65]], ['logins' => ['<=', 5]]]],
            '((%c%logins%c% >= %p1%) AND ((%c%logins%c% >= %p2%) OR (%c%logins%c% <= %p3%)))',
            ['1_logins' => 1, '2_logins' => 65, '3_logins' => 5],
            ['admin', 'viewer']
        );
    }

    /**
     * The exact OR-group example documented in the design spec and in README.md.
     */
    #[DataProvider('dialects')]
    public function testDocumentedOrGroupExampleAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect,
            ['username' => 'admin', 'OR' => [['role' => 'editor'], ['logins' => ['>=', 65]]]],
            '((%c%username%c% = %p1%) AND ((%c%role%c% = %p2%) OR (%c%logins%c% >= %p3%)))',
            ['1_username' => 'admin', '2_role' => 'editor', '3_logins' => 65],
            ['admin']
        );
    }

    /**
     * Multi-value operators nested inside a group - the placeholder token and the parameter
     * key must still line up after the tree-wide counter has advanced.
     */
    #[DataProvider('dialects')]
    public function testMultiValueOperatorsInsideGroupAcrossDialects(string $dialect)
    {
        $this->assertCondition(
            $dialect,
            ['OR' => [['username' => ['IN', ['admin', 'ghost']]], ['logins' => ['BETWEEN', 25, 35]]]],
            '((%c%username%c% IN (%p1%, %p2%)) OR (%c%logins%c% BETWEEN %p3% AND %p4%))',
            ['1_username' => 'admin', '2_username' => 'ghost', '3_logins' => 25, '4_logins' => 35],
            ['admin', 'editor', 'ghost']
        );
    }

    /**
     * Regression: a legacy entry on a column whose NAME matches the shape of another column's
     * generated parameter key must not have its value clobbered. With the old
     * '<column>_<n>' key format, ['line_1>' => 5, 'line' => ['=', 'admin']] rendered
     * (("line_1" > :line_1) AND ("line" = :line_1)) on SQLite and kept only one parameter.
     */
    #[DataProvider('dialects')]
    public function testLegacyKeyShapeDoesNotCollideWithGeneratedKeyAcrossDialects(string $dialect)
    {
        // The legacy path keys its parameters by column name only on ':' dialects; the '?' and
        // '$' dialects get positional integer keys, so only SQLite could ever collide here.
        $expectedParams = ($dialect === 'sqlite') ?
            ['line_1' => 5, '1_line' => 'admin'] : [0 => 5, '1_line' => 'admin'];

        $this->assertCondition(
            $dialect,
            ['line_1>' => 5, 'line' => ['=', 'admin']],
            '((%c%line_1%c% > %p1%) AND (%c%line%c% = %p2%))',
            $expectedParams,
            ['admin'],
            true
        );
    }

    /*
     * ---------------------------------------------------------------------------------------
     * Operator/shape unit coverage (MySQL)
     * ---------------------------------------------------------------------------------------
     */

    public function testEqualTo()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['email' => ['=', 'test@test.com']], $sql);

        $this->assertEquals('(`email` = ?)', $predicateSet->render());
        $this->assertEquals(['1_email' => 'test@test.com'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testGreaterThanOrEqualTo()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['logins' => ['>=', 18]], $sql);

        $this->assertEquals('(`logins` >= ?)', $predicateSet->render());
        $this->assertEquals(['1_logins' => 18], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testLikeAcceptsLowercaseOperator()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => ['like', '%smith%']], $sql);

        $this->assertEquals('(`username` LIKE ?)', $predicateSet->render());
        $this->assertEquals(['1_username' => '%smith%'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testPlainScalarStillMeansEquals()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => 'testuser'], $sql);

        $this->assertEquals('(`username` = ?)', $predicateSet->render());
        $this->assertEquals(['1_username' => 'testuser'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testOrGroupEntryMustBeArray()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['OR' => ['not-an-array', ['role' => 'editor']]], $sql);
    }

    public function testOrGroupSkipsEmptyEntries()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['OR' => [[], ['role' => 'editor']]], $sql);

        $this->assertEquals('(`role` = ?)', $predicateSet->render());
        $this->assertEquals(['1_role' => 'editor'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testWrongArityThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['logins' => ['>=', 18, 21]], $sql);
    }

    public function testArity0OperatorWithExtraValueThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['role' => ['IS NULL', 'unexpected']], $sql);
    }

    public function testJsonPathTupleWrongArityThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['data->$.name' => ['=', 'admin', 'extra']], $sql);
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
        $this->assertEquals(['1_username' => 'admin', '2_username' => 'editor'], $predicateSet->getParameters());
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

    public function testInWithEmptyArrayThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $this->expectExceptionMessage("requires at least 1 value, 0 given");
        $sql = $this->db->createSql();
        Condition::parse(['role' => ['IN', []]], $sql);
    }

    public function testNotInWithEmptyArrayThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $this->expectExceptionMessage("requires at least 1 value, 0 given");
        $sql = $this->db->createSql();
        Condition::parse(['role' => ['NOT IN', []]], $sql);
    }

    public function testInWithSubquery()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('id')->from('banned_users');
        $subquery->where->equalTo('reason', 'fraud');

        $predicateSet = Condition::parse(['user_id' => ['IN', $subquery]], $sql);

        $this->assertEquals(
            "(`user_id` IN (SELECT `id` FROM `banned_users` WHERE (`reason` = 'fraud')))",
            $predicateSet->render()
        );
        $this->assertEquals([], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testEqualToWithSubquery()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('MAX(total)')->from('orders');

        $predicateSet = Condition::parse(['total' => ['=', $subquery]], $sql);

        $this->assertEquals(
            "(`total` = (SELECT MAX(total) FROM `orders`))",
            $predicateSet->render()
        );
        $this->assertEquals([], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testComparisonOperatorsWithSubquery()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('MAX(total)')->from('orders');

        $this->assertEquals(
            "(`total` != (SELECT MAX(total) FROM `orders`))",
            Condition::parse(['total' => ['!=', $subquery]], $sql)->render()
        );
        $this->assertEquals(
            "(`total` > (SELECT MAX(total) FROM `orders`))",
            Condition::parse(['total' => ['>', $subquery]], $sql)->render()
        );
        $this->assertEquals(
            "(`total` >= (SELECT MAX(total) FROM `orders`))",
            Condition::parse(['total' => ['>=', $subquery]], $sql)->render()
        );
        $this->assertEquals(
            "(`total` < (SELECT MAX(total) FROM `orders`))",
            Condition::parse(['total' => ['<', $subquery]], $sql)->render()
        );
        $this->assertEquals(
            "(`total` <= (SELECT MAX(total) FROM `orders`))",
            Condition::parse(['total' => ['<=', $subquery]], $sql)->render()
        );
        $this->db->disconnect();
    }

    /**
     * The dispatcher methods' match() 'default' arms are structurally unreachable through
     * Condition::parse() - $method always comes from the class's own OPERATORS map - so they're
     * exercised directly via reflection to confirm the defensive throw actually fires.
     */
    public function testDispatcherMethodsThrowOnUnsupportedMethodName()
    {
        $sql          = $this->db->createSql();
        $predicateSet = new \Pop\Db\Sql\PredicateSet($sql);

        foreach (['callMulti', 'callArity0', 'callArity1', 'callArity1Mixed', 'callArity2'] as $dispatcher) {
            $method = new \ReflectionMethod(Condition::class, $dispatcher);
            $args   = match ($dispatcher) {
                'callMulti'       => [$predicateSet, 'bogus', 'col', ['a']],
                'callArity0'      => [$predicateSet, 'bogus', 'col'],
                'callArity1'      => [$predicateSet, 'bogus', 'col', 'val'],
                'callArity1Mixed' => [$predicateSet, 'bogus', 'col', $sql],
                'callArity2'      => [$predicateSet, 'bogus', 'col', 'val1', 'val2'],
            };

            try {
                $method->invoke(null, ...$args);
                $this->fail("$dispatcher did not throw for an unsupported method name");
            } catch (\Pop\Db\Sql\Parser\Exception $e) {
                $this->assertStringContainsString('Unsupported', $e->getMessage());
            }
        }
        $this->db->disconnect();
    }

    public function testLikeWithSubqueryThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('id')->from('banned_users');
        Condition::parse(['username' => ['LIKE', $subquery]], $sql);
    }

    public function testExistsKey()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('id')->from('orders');
        $subquery->where->equalTo('user_id', 5);

        $predicateSet = Condition::parse(['EXISTS' => $subquery], $sql);

        $this->assertEquals(
            "(EXISTS (SELECT `id` FROM `orders` WHERE (`user_id` = 5)))",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testNotExistsKey()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('id')->from('orders');
        $subquery->where->equalTo('user_id', 5);

        $predicateSet = Condition::parse(['NOT EXISTS' => $subquery], $sql);

        $this->assertEquals(
            "(NOT EXISTS (SELECT `id` FROM `orders` WHERE (`user_id` = 5)))",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testExistsKeyWithNonSelectValueThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['EXISTS' => 'not a select object'], $sql);
    }

    public function testExistsCombinedWithOtherConditions()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('id')->from('orders');
        $subquery->where->equalTo('user_id', 5);

        $predicateSet = Condition::parse(['active' => ['=', 1], 'EXISTS' => $subquery], $sql);

        $this->assertEquals(
            "((`active` = ?) AND (EXISTS (SELECT `id` FROM `orders` WHERE (`user_id` = 5))))",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testJsonEqualToShorthand()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['data->$.name' => ['=', 'admin']], $sql);

        $this->assertEquals(
            "(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) = ?)",
            $predicateSet->render()
        );
        $this->assertEquals(['1_data' => 'admin'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testJsonEqualToBareShorthand()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['data->$.name' => 'admin'], $sql);

        $this->assertEquals(
            "(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) = ?)",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testJsonNotEqualToShorthand()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['data->$.name' => ['!=', 'admin']], $sql);

        $this->assertEquals(
            "(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) != ?)",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testJsonContainsShorthand()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['data->$.roles' => ['CONTAINS', 'admin']], $sql);

        $this->assertEquals(
            "(JSON_CONTAINS(`data`, '\\\"admin\\\"', '$.roles'))",
            $predicateSet->render()
        );
        $this->assertEquals([], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testContainsWithoutJsonPathThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['data' => ['CONTAINS', 'admin']], $sql);
    }

    public function testJsonPathWithUnsupportedOperatorThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['data->$.name' => ['>', 'admin']], $sql);
    }

    /**
     * Regression: because PHP array keys are unique, two 'EXISTS' keys cannot coexist at the
     * top level of a shorthand array. Nesting them inside an OR/AND group is the documented
     * workaround, so it needs to keep working.
     */
    public function testExistsAndNotExistsNestedInOrGroup()
    {
        $sql = $this->db->createSql();

        $subqueryA = $this->db->createSql()->select('id')->from('orders');
        $subqueryA->where->equalTo('status', 'shipped');

        $subqueryB = $this->db->createSql()->select('id')->from('refunds');
        $subqueryB->where->equalTo('status', 'pending');

        $predicateSet = Condition::parse(
            ['OR' => [['EXISTS' => $subqueryA], ['NOT EXISTS' => $subqueryB]]], $sql
        );

        $this->assertEquals(
            "((EXISTS (SELECT `id` FROM `orders` WHERE (`status` = 'shipped'))) OR " .
            "(NOT EXISTS (SELECT `id` FROM `refunds` WHERE (`status` = 'pending'))))",
            $predicateSet->render()
        );
        $this->db->disconnect();
    }

    public function testBetween()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['logins' => ['BETWEEN', 5, 10]], $sql);

        $this->assertEquals('(`logins` BETWEEN ? AND ?)', $predicateSet->render());
        $this->assertEquals(['1_logins' => 5, '2_logins' => 10], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testBetweenWithCommaContainingValueDoesNotBreak()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['amount' => ['BETWEEN', '1,000', '2,000']], $sql);

        $this->assertEquals('(`amount` BETWEEN ? AND ?)', $predicateSet->render());
        $this->assertEquals(['1_amount' => '1,000', '2_amount' => '2,000'], $predicateSet->getParameters());
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
        $this->assertEquals(['1_discount_' => 5], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testTableQualifiedColumnProducesBindSafeParameterKey()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['users.username' => ['=', 'admin']], $sql);

        $this->assertEquals('(`users`.`username` = ?)', $predicateSet->render());
        $this->assertEquals(['1_users_username' => 'admin'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    /*
     * ---------------------------------------------------------------------------------------
     * Legacy classification / deprecation
     * ---------------------------------------------------------------------------------------
     */

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

    public function testPlainScalarEqualityDoesNotTriggerDeprecation()
    {
        $sql = $this->db->createSql();
        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $predicateSet = Condition::parse(['username' => 'admin', 'logins' => 5], $sql);

        restore_error_handler();
        $this->assertFalse(
            $deprecationTriggered, 'Plain scalar equality is first-class new syntax and must not be deprecated.'
        );
        $this->assertEquals('((`username` = ?) AND (`logins` = ?))', $predicateSet->render());
        $this->assertEquals(['1_username' => 'admin', '2_logins' => 5], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testBareKeyNullValueProducesIsNullWithoutDeprecation()
    {
        $sql = $this->db->createSql();
        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $predicateSet = Condition::parse(['deleted_at' => null], $sql);

        restore_error_handler();
        $this->assertFalse($deprecationTriggered);
        $this->assertEquals('(`deleted_at` IS NULL)', $predicateSet->render());
        $this->assertFalse($predicateSet->hasParameters());
        $this->db->disconnect();
    }

    public function testLegacyNotNullSuffixWithNullValueStillProducesIsNotNull()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['deleted_at-' => null], $sql);

        $this->assertEquals('(`deleted_at` IS NOT NULL)', $predicateSet->render());
        $this->db->disconnect();
    }

    /**
     * The legacy packed BETWEEN shape ('column' => '(v1, v2)') is a bare key with a scalar
     * value, but it must NOT be swept into the plain-equality fast path - it keeps its legacy
     * BETWEEN meaning (and its deprecation notice).
     */
    public function testLegacyPackedBetweenStringStillRoutesThroughLegacy()
    {
        $sql          = $this->db->createSql();
        $predicateSet = @Condition::parse(['logins' => '(0, 10)'], $sql);

        $this->assertEquals('(`logins` BETWEEN ? AND ?)', $predicateSet->render());
        $params = $predicateSet->getParameters();
        $this->assertContains('0', $params);
        $this->assertContains('10', $params);
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
            $db = $this->connect('pgsql');
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

    public function testPostgresParameterCountingNoDoubleIncrement()
    {
        // Regression test for AbstractSql::getParameter() double-counting bug
        // where unconditional incrementParameterCount() at method top caused
        // PostgreSQL placeholders to skip ($1, $3, $5 instead of $1, $2, $3)
        if (!extension_loaded('pgsql')) {
            $this->markTestSkipped('PostgreSQL extension not loaded');
        }

        try {
            $db = $this->connect('pgsql');
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to PostgreSQL: ' . $e->getMessage());
        }

        $sql          = $db->createSql();
        // Two new-syntax predicates that will use nextPlaceholder() and getParameter()
        $predicateSet = Condition::parse(['logins' => ['>=', 18], 'age' => ['<', 30]], $sql);

        // Before fix: render was '(("logins" >= $1) AND ("age" < $3))' (skipped $2)
        // After fix: correct sequential numbering
        $render = $predicateSet->render();
        $this->assertEquals('(("logins" >= $1) AND ("age" < $2))', $render);

        $params = $predicateSet->getParameters();
        $this->assertContains(18, $params);
        $this->assertContains(30, $params);
        $this->assertEquals(2, count($params));

        $db->disconnect();
    }

    public function testPostgresPlaceholderNumberingWithMixedLegacyAndNewSyntax()
    {
        if (!extension_loaded('pgsql')) {
            $this->markTestSkipped('PostgreSQL extension not loaded');
        }

        try {
            $db = $this->connect('pgsql');
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to PostgreSQL: ' . $e->getMessage());
        }

        $sql = $db->createSql();
        // The legacy bucket is always processed first, so its $n tokens come first regardless
        // of the order the caller wrote the keys in.
        $predicateSet = @Condition::parse(['logins' => ['>=', 18], 'username>' => 'a'], $sql);

        $this->assertEquals('(("username" > $1) AND ("logins" >= $2))', $predicateSet->render());
        $db->disconnect();
    }

    /*
     * ---------------------------------------------------------------------------------------
     * OR/AND grouping
     * ---------------------------------------------------------------------------------------
     */

    public function testOrGroup()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse([
            'username' => ['=', 'admin'],
            'OR' => [
                ['logins' => ['>=', 65]],
                ['email' => ['=', 'vip@test.com']],
            ],
        ], $sql);

        $this->assertEquals(
            '((`username` = ?) AND ((`logins` >= ?) OR (`email` = ?)))', $predicateSet->render()
        );
        $this->assertEquals(
            ['1_username' => 'admin', '2_logins' => 65, '3_email' => 'vip@test.com'],
            $predicateSet->getParameters()
        );
        $this->db->disconnect();
    }

    public function testOrGroupWithOnlyGroupNoDanglingConjunction()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse([
            'OR' => [
                ['logins' => ['>=', 65]],
                ['email' => ['=', 'vip@test.com']],
            ],
        ], $sql);

        $this->assertEquals('((`logins` >= ?) OR (`email` = ?))', $predicateSet->render());
        $this->db->disconnect();
    }

    public function testNestedOrInsideAndGroup()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse([
            'AND' => [
                ['username' => ['=', 'admin']],
                ['OR' => [
                    ['logins' => ['>=', 65]],
                    ['email' => ['=', 'vip@test.com']],
                ]],
            ],
        ], $sql);

        $this->assertEquals(
            '((`username` = ?) AND ((`logins` >= ?) OR (`email` = ?)))', $predicateSet->render()
        );
        $this->assertEquals(
            ['1_username' => 'admin', '2_logins' => 65, '3_email' => 'vip@test.com'],
            $predicateSet->getParameters()
        );
        $this->db->disconnect();
    }

    public function testEmptyGroupIsNoOp()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['username' => ['=', 'admin'], 'OR' => []], $sql);

        $this->assertEquals('(`username` = ?)', $predicateSet->render());
        $this->db->disconnect();
    }

    public function testGroupWithNonArrayValueThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['OR' => 'not-an-array'], $sql);
    }

    public function testLegacySyntaxInsideGroupThrows()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $sql = $this->db->createSql();
        Condition::parse(['OR' => [['logins>=' => 65]]], $sql);
    }

    /**
     * The documented example from the design spec and README.md - a bare scalar inside an
     * OR group is plain equality (new syntax), not legacy, so it must not throw.
     */
    public function testDocumentedOrGroupExampleDoesNotThrow()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse([
            'status' => 'active',
            'OR'     => [
                ['role' => 'admin'],
                ['age'  => ['>=', 65]],
            ],
        ], $sql);

        $this->assertEquals(
            '((`status` = ?) AND ((`role` = ?) OR (`age` >= ?)))', $predicateSet->render()
        );
        $this->assertEquals(
            ['1_status' => 'active', '2_role' => 'admin', '3_age' => 65], $predicateSet->getParameters()
        );
        $this->db->disconnect();
    }

    public function testBareNullInsideGroupProducesIsNull()
    {
        $sql          = $this->db->createSql();
        $predicateSet = Condition::parse(['OR' => [['role' => null], ['logins' => ['>=', 65]]]], $sql);

        $this->assertEquals('((`role` IS NULL) OR (`logins` >= ?))', $predicateSet->render());
        $this->assertEquals(['1_logins' => 65], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    /*
     * ---------------------------------------------------------------------------------------
     * PARSEFILTER-HANDOFF.md §2/§3: Expression::convertExpressionsToStructured()
     * ---------------------------------------------------------------------------------------
     */

    /**
     * §3.1 - every operator class must render identical SQL and bind identical parameter
     * values whether the expression is converted via the legacy shorthand path or the new
     * structured path. The legacy call is deprecation-suppressed (@) since the deprecation
     * itself is asserted elsewhere (testLegacySyntaxTriggersDeprecation); this test is only
     * about output equivalence.
     */
    public function testStructuredConversionRendersIdenticallyToLegacyForEveryOperatorClass()
    {
        $expressions = [
            "id = 1",
            "id != 1",
            "logins >= 18",
            "logins < 65",
            "username LIKE '%adm%'",
            "username NOT LIKE '%host'",
            "id IN (1, 2, 3)",
            "id NOT IN (1, 2)",
            "logins BETWEEN 18 AND 65",
            "logins NOT BETWEEN 18 AND 65",
            "role IS NULL",
            "role IS NOT NULL",
        ];

        foreach ($expressions as $expression) {
            $legacyPredicateSet = @Condition::parse(
                Expression::convertExpressionsToShorthand([$expression]), $this->db->createSql()
            );
            $structuredPredicateSet = Condition::parse(
                Expression::convertExpressionsToStructured([$expression]), $this->db->createSql()
            );

            $this->assertEquals(
                $legacyPredicateSet->render(), $structuredPredicateSet->render(), $expression
            );
            $this->assertEquals(
                array_values($legacyPredicateSet->getParameters()),
                array_values($structuredPredicateSet->getParameters()),
                $expression
            );
        }

        $this->db->disconnect();
    }

    /**
     * §3.1 - and confirms the new path never fires the deprecation the legacy path does for
     * the exact same input.
     */
    public function testStructuredConversionNeverTriggersDeprecation()
    {
        $expressions = [
            "id = 1", "logins >= 18", "username LIKE '%adm%'", "id IN (1, 2, 3)",
            "logins BETWEEN 18 AND 65", "role IS NULL",
        ];

        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        Condition::parse(Expression::convertExpressionsToStructured($expressions), $this->db->createSql());

        restore_error_handler();
        $this->assertFalse($deprecationTriggered);
        $this->db->disconnect();
    }

    /**
     * §3.2 - the critical case. Two expressions on the same column must both survive as a
     * bounded range rather than the second overwriting the first, which is what a naive
     * ['column' => tuple] merge would do.
     */
    public function testStructuredConversionRepeatedColumnProducesBoundedRange()
    {
        $predicateSet = Condition::parse(Expression::convertExpressionsToStructured([
            'logins >= 18',
            'logins <= 65',
        ]), $this->db->createSql());

        $this->assertEquals('((`logins` >= ?) AND (`logins` <= ?))', $predicateSet->render());
        $this->assertEquals(['18', '65'], array_values($predicateSet->getParameters()));
        $this->db->disconnect();
    }

    /**
     * §3.2 - three-or-more repeats on the same column, and confirms every generated
     * parameter key is unique (Condition.php's $parameterIndex is threaded by reference for
     * exactly this reason).
     */
    public function testStructuredConversionThreeRepeatedColumnsProduceUniqueParameterKeys()
    {
        $predicateSet = Condition::parse(Expression::convertExpressionsToStructured([
            'logins >= 1',
            'logins <= 100',
            'logins != 50',
        ]), $this->db->createSql());

        // The first occurrence renders as a direct top-level predicate; the second and third
        // occurrences are combined into the nested 'AND' group's own predicate set - both
        // still AND together to the same overall meaning.
        $this->assertEquals(
            '((`logins` >= ?) AND ((`logins` <= ?) AND (`logins` != ?)))', $predicateSet->render()
        );
        $params = $predicateSet->getParameters();
        $this->assertEquals(3, count($params));
        $this->assertEquals(3, count(array_unique(array_keys($params))));
        $this->db->disconnect();
    }

    /**
     * §3.3 - a string key is an already-prepared column => value pair, not an expression to
     * parse; mixing one into the same filter array must not TypeError.
     */
    public function testStructuredConversionMixedKeysDoesNotTypeError()
    {
        $structured = Expression::convertExpressionsToStructured([
            'status' => 5,
            'logins >= 18',
        ]);

        $predicateSet = Condition::parse($structured, $this->db->createSql());

        $this->assertEquals('((`status` = ?) AND (`logins` >= ?))', $predicateSet->render());
        $this->db->disconnect();
    }

    /**
     * §3.4 - an entry that is already a structured condition array is merged as-is, with no
     * double-processing.
     */
    public function testStructuredConversionPreStructuredEntryPassesThrough()
    {
        $structured = Expression::convertExpressionsToStructured([
            ['id' => ['IN', [1, 2, 3]]],
        ]);

        $this->assertEquals(['id' => ['IN', [1, 2, 3]]], $structured);
    }

    /**
     * §3.5/§4.1 - a malformed expression string surfaces the parser's own exception rather
     * than silently falling back to the legacy (still-deprecated) path.
     */
    public function testStructuredConversionThrowsOnMalformedExpression()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        Expression::convertExpressionsToStructured(['username <> admin']);
    }

    /*
     * ---------------------------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------------------------------
     */

    /**
     * Assert a condition array renders correctly, binds correctly and returns the correct
     * rows when actually executed against the given live database
     */
    protected function assertCondition(
        string $dialect, array $conditions, string $template, array $expectedParams, array $expectedUsernames,
        bool $legacy = false
    ): void
    {
        $db = $this->connect($dialect);
        $this->seed($db);

        $sql          = $db->createSql();
        $predicateSet = ($legacy) ? @Condition::parse($conditions, $sql) : Condition::parse($conditions, $sql);

        $this->assertEquals(
            $this->expectedRender($dialect, $template, $expectedParams), $predicateSet->render()
        );
        $this->assertEquals($expectedParams, $predicateSet->getParameters());
        $this->assertEquals($expectedUsernames, $this->execute($db, $sql, $predicateSet));

        $db->disconnect();
    }

    /**
     * Expand a dialect-agnostic render template: %c% is the ID quote character and %pN% is
     * the Nth placeholder token. For the ':' dialects the Nth token is derived from the Nth
     * expected parameter KEY - which is exactly the token/key correspondence PDO and SQLite
     * bind on.
     */
    protected function expectedRender(string $dialect, string $template, array $expectedParams): string
    {
        $keys   = array_keys($expectedParams);
        $render = str_replace('%c%', ($dialect === 'mysql') ? '`' : '"', $template);

        for ($i = count($keys); $i >= 1; $i--) {
            $token = match ($dialect) {
                'mysql'  => '?',
                'pgsql'  => '$' . $i,
                'sqlite' => ':' . $keys[$i - 1],
            };
            $render = str_replace('%p' . $i . '%', $token, $render);
        }

        return $render;
    }

    /**
     * Prepare, bind and execute the predicate set against the live database
     */
    protected function execute($db, $sql, $predicateSet): array
    {
        $sql->select(['username'])->from('cond_users')->where($predicateSet)->orderBy('username');

        $parameters = $predicateSet->getParameters();

        $db->prepare((string)$sql);
        if (count($parameters) > 0) {
            $db->bindParams($parameters);
        }
        $db->execute();

        return array_map(fn($row) => $row['username'], $db->fetchAll());
    }

    protected static function connect(string $dialect)
    {
        return match ($dialect) {
            'mysql'  => Db::mysqlConnect([
                'database' => $_ENV['MYSQL_DB'],
                'username' => $_ENV['MYSQL_USER'],
                'password' => $_ENV['MYSQL_PASS'],
                'host'     => $_ENV['MYSQL_HOST']
            ]),
            'pgsql'  => Db::pgsqlConnect([
                'database' => $_ENV['PGSQL_DB'] ?? 'test_popdb',
                'username' => $_ENV['PGSQL_USER'] ?? 'postgres',
                'password' => $_ENV['PGSQL_PASS'] ?? 'postgres',
                'host'     => $_ENV['PGSQL_HOST'] ?? '127.0.0.1'
            ]),
            'sqlite' => self::sqliteConnect(),
        };
    }

    protected static function sqliteConnect()
    {
        $file = __DIR__ . '/../../tmp/condition.sqlite';

        chmod(__DIR__ . '/../../tmp', 0777);
        touch($file);
        chmod($file, 0777);

        return Db::sqliteConnect(['database' => $file]);
    }

    protected function seed($db): void
    {
        $schema = $db->createSchema();
        $schema->dropIfExists('cond_users');
        $schema->execute();

        $schema->create('cond_users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('email', 255)
            ->int('logins', 16)->defaultIs(0)
            ->varchar('role', 255)->nullable()
            // 'line' and 'line_1' exist purely so that one column's name matches the shape of
            // another column's generated parameter key - see the key-collision regression test
            ->varchar('line', 255)->nullable()
            ->int('line_1', 16)->defaultIs(0)
            ->primary('id');

        $schema->execute();

        $rows = [
            ['admin',  'admin@test.com',  70, 'admin'],
            ['editor', 'editor@test.com', 30, 'editor'],
            ['viewer', 'viewer@test.com',  3, 'viewer'],
            ['ghost',  'ghost@test.com',   0, null],
        ];

        foreach ($rows as $row) {
            $role = ($row[3] === null) ? 'NULL' : "'" . $row[3] . "'";
            $db->query(
                "INSERT INTO cond_users (username, email, logins, role, line, line_1) VALUES " .
                "('" . $row[0] . "', '" . $row[1] . "', " . $row[2] . ", " . $role . ", " .
                "'" . $row[0] . "', " . $row[2] . ")"
            );
        }
    }

}
