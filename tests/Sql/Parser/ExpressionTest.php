<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Db;
use Pop\Db\Sql\Parser\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
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

    public function tearDown(): void
    {
        $this->db->disconnect();
    }

    public function testParse()
    {
        $expressions = [
            "username = 'admin'",
            "email = 'test@test.com'",
            "attempts >= 5",
            "role IS NULL",
            "title LIKE 'CEO%'",
            "id IN (1, 2, 3)",
            "logins BETWEEN 50 AND 100"
        ];

        $components = Expression::parseExpressions($expressions);

        $this->assertEquals(7, count($components));

        $this->assertEquals(3, count($components[0]));
        $this->assertTrue(isset($components[0]['column']));
        $this->assertTrue(isset($components[0]['operator']));
        $this->assertTrue(isset($components[0]['value']));
        $this->assertEquals('username', $components[0]['column']);
        $this->assertEquals('=', $components[0]['operator']);
        $this->assertEquals('admin', $components[0]['value']);

        $this->assertEquals(3, count($components[1]));
        $this->assertTrue(isset($components[1]['column']));
        $this->assertTrue(isset($components[1]['operator']));
        $this->assertTrue(isset($components[1]['value']));
        $this->assertEquals('email', $components[1]['column']);
        $this->assertEquals('=', $components[1]['operator']);
        $this->assertEquals('test@test.com', $components[1]['value']);

        $this->assertEquals(3, count($components[2]));
        $this->assertTrue(isset($components[2]['column']));
        $this->assertTrue(isset($components[2]['operator']));
        $this->assertTrue(isset($components[2]['value']));
        $this->assertEquals('attempts', $components[2]['column']);
        $this->assertEquals('>=', $components[2]['operator']);
        $this->assertEquals('5', $components[2]['value']);

        $this->assertEquals(3, count($components[3]));
        $this->assertTrue(isset($components[3]['column']));
        $this->assertTrue(isset($components[3]['operator']));
        $this->assertEquals('role', $components[3]['column']);
        $this->assertEquals('IS NULL', $components[3]['operator']);
        $this->assertNull($components[3]['value']);

        $this->assertEquals(3, count($components[4]));
        $this->assertTrue(isset($components[4]['column']));
        $this->assertTrue(isset($components[4]['operator']));
        $this->assertTrue(isset($components[4]['value']));
        $this->assertEquals('title', $components[4]['column']);
        $this->assertEquals('LIKE', $components[4]['operator']);
        $this->assertEquals('CEO%', $components[4]['value']);

        $this->assertEquals(3, count($components[5]));
        $this->assertTrue(isset($components[5]['column']));
        $this->assertTrue(isset($components[5]['operator']));
        $this->assertTrue(isset($components[5]['value']));
        $this->assertEquals('id', $components[5]['column']);
        $this->assertEquals('IN', $components[5]['operator']);
        $this->assertEquals(3, count($components[5]['value']));
        $this->assertEquals('1', $components[5]['value'][0]);
        $this->assertEquals('2', $components[5]['value'][1]);
        $this->assertEquals('3', $components[5]['value'][2]);

        $this->assertEquals(3, count($components[6]));
        $this->assertTrue(isset($components[6]['column']));
        $this->assertTrue(isset($components[6]['operator']));
        $this->assertTrue(isset($components[6]['value']));
        $this->assertEquals('logins', $components[6]['column']);
        $this->assertEquals('BETWEEN', $components[6]['operator']);
        $this->assertEquals('(50 AND 100)', $components[6]['value']);
//        $this->assertEquals(2, count($components[6]['value']));
//        $this->assertEquals('50', $components[6]['value'][0]);
//        $this->assertEquals('100', $components[6]['value'][1]);
    }

    public function testNotFlattened()
    {
        $expressions = [
            "username = 'admin'",
            "email = 'test@test.com'",
            "attempts >= 5",
            "role IS NULL",
            "title LIKE 'CEO%'",
            "id IN (1, 2, 3)",
            "logins BETWEEN 50 AND 100"
        ];

        $shortHand = Expression::convertExpressionsToShorthand($expressions);
        $results   = Expression::parseShorthand($shortHand, '?', false);

        $this->assertTrue(isset($results['expressions']));
        $this->assertTrue(isset($results['params']));
        $this->assertTrue(is_array($results['expressions']));
        $this->assertTrue(is_array($results['params']));
        $this->assertEquals(7, count($results['expressions']));
        $this->assertEquals(6, count($results['params']));
        $this->assertTrue(isset($results['expressions'][0]));
        $this->assertTrue(isset($results['expressions'][1]));
        $this->assertTrue(isset($results['expressions'][2]));
        $this->assertTrue(isset($results['expressions'][3]));
        $this->assertTrue(isset($results['expressions'][4]));
        $this->assertTrue(isset($results['expressions'][5]));
        $this->assertTrue(isset($results['expressions'][6]));
        $this->assertFalse(isset($results['expressions'][7]));
        $this->assertTrue(isset($results['params'][0]));
        $this->assertTrue(isset($results['params'][1]));
        $this->assertTrue(isset($results['params'][2]));
        $this->assertFalse(isset($results['params'][3]));
        $this->assertTrue(isset($results['params'][4]));
        $this->assertTrue(isset($results['params'][5]));
        $this->assertTrue(isset($results['params'][6]));
        $this->assertFalse(isset($results['params'][7]));
    }

    public function testParseException()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $component = Expression::parse("username <> 'admin'");
    }

    public function testConvertExpressionsToShorthand()
    {
        $expressions = [
            "username = 'admin'",
            "email = 'test@test.com'",
            "attempts >= 5",
            "role IS NULL",
            "profile IS NOT NULL",
            "title LIKE '%CEO%'",
            "position NOT LIKE '%Staff%'",
            "id IN (1, 2, 3)",
            "logins BETWEEN 50 AND 100"
        ];

        $columns = Expression::convertExpressionsToShorthand($expressions);

        $this->assertTrue(isset($columns['username']));
        $this->assertTrue(isset($columns['email']));
        $this->assertTrue(isset($columns['attempts>=']));
        $this->assertTrue(array_key_exists('role', $columns));
        $this->assertTrue(array_key_exists('profile-', $columns));
        $this->assertTrue(isset($columns['%title%']));
        $this->assertTrue(isset($columns['-%position%-']));
        $this->assertTrue(isset($columns['id']));
        $this->assertTrue(isset($columns['logins']));

        $this->assertEquals('admin', $columns['username']);
        $this->assertEquals('test@test.com', $columns['email']);
        $this->assertEquals('5', $columns['attempts>=']);
        $this->assertNull($columns['role']);
        $this->assertNull($columns['profile-']);
        $this->assertEquals('CEO', $columns['%title%']);
        $this->assertEquals('Staff', $columns['-%position%-']);
        $this->assertTrue(is_array($columns['id']));
        $this->assertEquals(3, count($columns['id']));
        $this->assertEquals(1, $columns['id'][0]);
        $this->assertEquals(2, $columns['id'][1]);
        $this->assertEquals(3, $columns['id'][2]);
        $this->assertEquals('(50 AND 100)', $columns['logins']);
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.1 - every operator class must round-trip into the structured
     * operator-tuple format that Sql\Parser\Condition::parseConditions() accepts natively.
     */
    public function testConvertExpressionsToStructuredCoversEveryOperatorClass()
    {
        $cases = [
            "id = 1"                    => ['id' => ['=', '1']],
            "id != 1"                   => ['id' => ['!=', '1']],
            "age >= 18"                 => ['age' => ['>=', '18']],
            "age < 65"                  => ['age' => ['<', '65']],
            "name LIKE '%smith%'"       => ['name' => ['LIKE', '%smith%']],
            "name NOT LIKE '%smith'"    => ['name' => ['NOT LIKE', '%smith']],
            "id IN (1, 2, 3)"           => ['id' => ['IN', ['1', '2', '3']]],
            "id NOT IN (1, 2)"          => ['id' => ['NOT IN', ['1', '2']]],
            "age BETWEEN 18 AND 65"     => ['age' => ['BETWEEN', '18', '65']],
            "age NOT BETWEEN 18 AND 65" => ['age' => ['NOT BETWEEN', '18', '65']],
            "deleted IS NULL"           => ['deleted' => ['IS NULL']],
            "deleted IS NOT NULL"       => ['deleted' => ['IS NOT NULL']],
        ];

        foreach ($cases as $expression => $expected) {
            $this->assertEquals($expected, Expression::convertExpressionsToStructured([$expression]), $expression);
        }
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.2 - the critical case: two expressions on the same column must
     * both survive, routed into a nested 'AND' group, rather than the second silently
     * overwriting the first the way a naive ['column' => tuple] merge would.
     */
    public function testConvertExpressionsToStructuredRepeatedColumnRoutesIntoAndGroup()
    {
        // The first occurrence of a repeated column keeps its direct 'column' => tuple slot;
        // only the second-and-later occurrences are routed into the 'AND' group. Both still
        // apply - Condition::parseConditions() ANDs every top-level entry together regardless
        // of which bucket it came from - this only avoids the second occurrence overwriting
        // the first at the same array key.
        $result = Expression::convertExpressionsToStructured([
            'due_date >= 2026-01-01',
            'due_date <= 2026-12-31',
        ]);

        $this->assertEquals(['>=', '2026-01-01'], $result['due_date']);
        $this->assertEquals([
            ['due_date' => ['<=', '2026-12-31']],
        ], $result['AND']);
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.2 - three-or-more repeats on the same column.
     */
    public function testConvertExpressionsToStructuredThreeRepeatedColumns()
    {
        $result = Expression::convertExpressionsToStructured([
            'logins >= 1',
            'logins <= 100',
            'logins != 50',
        ]);

        $this->assertEquals(['>=', '1'], $result['logins']);
        $this->assertEquals([
            ['logins' => ['<=', '100']],
            ['logins' => ['!=', '50']],
        ], $result['AND']);
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.3 - a string key is an already-prepared column => value pair
     * rather than an expression string; mixing one into the same filter array must not
     * TypeError trying to parse it as an expression.
     */
    public function testConvertExpressionsToStructuredMixedKeysDoesNotTypeError()
    {
        $result = Expression::convertExpressionsToStructured([
            'status' => 5,
            'due_date >= 2026-01-01',
        ]);

        $this->assertEquals(5, $result['status']);
        $this->assertEquals(['>=', '2026-01-01'], $result['due_date']);
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.4 - an entry that is already a structured condition array is
     * merged as-is, with no double-processing.
     */
    public function testConvertExpressionsToStructuredPreStructuredEntryPassesThrough()
    {
        $result = Expression::convertExpressionsToStructured([
            ['id' => ['IN', [1, 2, 3]]],
        ]);

        $this->assertEquals(['id' => ['IN', [1, 2, 3]]], $result);
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.5/§4.1 - a malformed expression string surfaces the parser's
     * own exception rather than silently falling back to the legacy (still-deprecated) path.
     */
    public function testConvertExpressionsToStructuredThrowsOnMalformedExpression()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        Expression::convertExpressionsToStructured(['username <> admin']);
    }

    public function testParseShorthand()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns);

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = 'admin'", $parsed['expressions'][0]);
        $this->assertEquals("email = 'test@test.com'", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= 5", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE '%CEO%'", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE '%Staff%'", $parsed['expressions'][6]);
        $this->assertEquals("id IN (1, 2, 3)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN 50 AND 100", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder1()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, '?');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = ?", $parsed['expressions'][0]);
        $this->assertEquals("email = ?", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= ?", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE ?", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE ?", $parsed['expressions'][6]);
        $this->assertEquals("id IN (?, ?, ?)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN ? AND ?", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder2()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, '$');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = $1", $parsed['expressions'][0]);
        $this->assertEquals("email = $2", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= $3", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE $4", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE $5", $parsed['expressions'][6]);
        $this->assertEquals("id IN ($6, $7, $8)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN $9 AND $10", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder3()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, ':');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = :username", $parsed['expressions']['username']);
        $this->assertEquals("email = :email", $parsed['expressions']['email']);
        $this->assertEquals("attempts >= :attempts", $parsed['expressions']['attempts']);
        $this->assertEquals("role IS NULL", $parsed['expressions']['role']);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions']['profile']);
        $this->assertEquals("title LIKE :title", $parsed['expressions']['title']);
        $this->assertEquals("position NOT LIKE :position", $parsed['expressions']['position']);
        $this->assertEquals("id IN (:id1, :id2, :id3)", $parsed['expressions']['id']);
        $this->assertEquals("logins BETWEEN :logins1 AND :logins2", $parsed['expressions']['logins']);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params']['username']);
        $this->assertEquals('test@test.com', $parsed['params']['email']);
        $this->assertEquals('5', $parsed['params']['attempts']);
        $this->assertEquals('%CEO%', $parsed['params']['title']);
        $this->assertEquals('%Staff%', $parsed['params']['position']);
        $this->assertEquals(1, $parsed['params']['id1']);
        $this->assertEquals(2, $parsed['params']['id2']);
        $this->assertEquals(3, $parsed['params']['id3']);
        $this->assertEquals('50', $parsed['params']['logins1']);
        $this->assertEquals('100', $parsed['params']['logins2']);
    }

    public function testStripIdQuotes()
    {
        $this->assertEquals('username', Expression::stripIdQuotes('`username`'));
    }

    public function testParseIgnoresBetweenKeywordInsideQuotedValue()
    {
        $result = Expression::parse("status = 'PENDING BETWEEN REVIEW'");
        $this->assertEquals('status', $result['column']);
        $this->assertEquals('=', $result['operator']);
        $this->assertEquals('PENDING BETWEEN REVIEW', $result['value']);
    }

    public function testParseIgnoresLikeKeywordInsideQuotedValue()
    {
        $result = Expression::parse("status = 'ORDER LIKE THIS'");
        $this->assertEquals('status', $result['column']);
        $this->assertEquals('=', $result['operator']);
        $this->assertEquals('ORDER LIKE THIS', $result['value']);
    }

    public function testParseStillDetectsRealBetween()
    {
        $result = Expression::parse('attempts BETWEEN 5 AND 10');
        $this->assertEquals('attempts', $result['column']);
        $this->assertEquals('BETWEEN', $result['operator']);
    }

    public function testPrepareExpressionEquals()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression("username = 'admin'", $sql);
        $this->assertEquals('`username` = ?', $result['clause']);
        $this->assertEquals(['username' => 'admin'], $result['params']);
    }

    public function testPrepareExpressionEqualsWithoutParams()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression("username = 'admin'", $sql, false);
        $this->assertEquals("`username` = 'admin'", $result['clause']);
        $this->assertEquals([], $result['params']);
    }

    public function testPrepareExpressionBetween()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression('logins BETWEEN 50 AND 100', $sql);
        $this->assertEquals('`logins` BETWEEN ? AND ?', $result['clause']);
        $this->assertEquals(['logins' => ['50', '100']], $result['params']);
    }

    public function testPrepareExpressionBetweenWithoutParams()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression('logins BETWEEN 50 AND 100', $sql, false);
        // quote() leaves purely-numeric values unquoted
        $this->assertEquals('`logins` BETWEEN 50 AND 100', $result['clause']);
        $this->assertEquals([], $result['params']);
    }

    public function testPrepareExpressionIn()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression('id IN (1, 2, 3)', $sql);
        $this->assertEquals('`id` IN (?, ?, ?)', $result['clause']);
        $this->assertEquals(['id' => ['1', '2', '3']], $result['params']);
    }

    public function testPrepareExpressionInWithoutParams()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression('id IN (1, 2, 3)', $sql, false);
        // quote() leaves purely-numeric values unquoted
        $this->assertEquals('`id` IN (1, 2, 3)', $result['clause']);
        $this->assertEquals([], $result['params']);
    }

    public function testPrepareExpressionIsNullHasNoValue()
    {
        $sql    = $this->db->createSql();
        $result = Expression::prepareExpression('role IS NULL', $sql);
        $this->assertEquals('`role` IS NULL', $result['clause']);
        $this->assertEquals([], $result['params']);
    }

    public function testPrepareExpressionsFlattenedWithQuestionMarkPlaceholder()
    {
        $sql     = $this->db->createSql();
        $results = Expression::prepareExpressions(["username = 'admin'", 'id IN (1, 2, 3)'], $sql);

        $this->assertEquals(['`username` = ?', '`id` IN (?, ?, ?)'], $results['clauses']);
        // Each expression's whole params entry is appended as one element - a
        // multi-value entry (like IN's) stays nested rather than being unpacked
        $this->assertEquals(['admin', ['1', '2', '3']], $results['params']);
    }

    public function testPrepareExpressionsFlattenedWithNamedPlaceholder()
    {
        touch(__DIR__ . '/../../tmp/prepare_expressions.sqlite');
        $sqlite = Db::sqliteConnect(['database' => __DIR__ . '/../../tmp/prepare_expressions.sqlite']);
        $sql    = $sqlite->createSql();

        // getPlaceholder() returns the bare ':' character for sqlite (not a unique
        // named token), so the flattened params are keyed by column+count instead
        $results = Expression::prepareExpressions(["username = 'admin'", 'id IN (1, 2, 3)'], $sql);

        $this->assertEquals(['"username" = :', '"id" IN (:, :, :)'], $results['clauses']);
        $this->assertEquals(['username1' => 'admin', 'id1' => ['1', '2', '3']], $results['params']);

        $sqlite->disconnect();
        @unlink(__DIR__ . '/../../tmp/prepare_expressions.sqlite');
    }

    public function testPrepareExpressionsNotFlattened()
    {
        $sql     = $this->db->createSql();
        $results = Expression::prepareExpressions(["username = 'admin'", 'id IN (1, 2, 3)'], $sql, true, false);

        $this->assertEquals(2, count($results));
        $this->assertEquals('`username` = ?', $results[0]['clause']);
        $this->assertEquals('`id` IN (?, ?, ?)', $results[1]['clause']);
    }


    public function testParseKeepsFunctionArgumentsContainingSpacesWithTheColumn()
    {
        // The column runs to the first space that is not inside parentheses, so a function
        // call carrying spaces in its argument list stays intact
        $this->assertEquals(
            ['column' => 'COUNT(DISTINCT id)', 'operator' => '>', 'value' => '1'],
            Expression::parse('COUNT(DISTINCT id) > 1')
        );
        $this->assertEquals(
            ['column' => 'ROUND(price, 2)', 'operator' => '>=', 'value' => '3'],
            Expression::parse('ROUND(price, 2) >= 3')
        );
        $this->assertEquals(
            ['column' => 'MAX(users.id)', 'operator' => '<', 'value' => '9'],
            Expression::parse('MAX(users.id) < 9')
        );
    }

    public function testParseKeepsFunctionArgumentsWithSpacesForEveryOperatorBranch()
    {
        $isNull = Expression::parse('COUNT(DISTINCT id) IS NULL');
        $this->assertEquals('COUNT(DISTINCT id)', $isNull['column']);
        $this->assertEquals('IS NULL', $isNull['operator']);

        $isNotNull = Expression::parse('COUNT(DISTINCT id) IS NOT NULL');
        $this->assertEquals('COUNT(DISTINCT id)', $isNotNull['column']);
        $this->assertEquals('IS NOT NULL', $isNotNull['operator']);

        // The value list has to be found after the column, not at the function's own paren
        $in = Expression::parse('COUNT(DISTINCT id) IN (1, 2)');
        $this->assertEquals('COUNT(DISTINCT id)', $in['column']);
        $this->assertEquals('IN', $in['operator']);
        $this->assertEquals(['1', '2'], $in['value']);

        $notIn = Expression::parse('COUNT(DISTINCT id) NOT IN (1, 2)');
        $this->assertEquals('COUNT(DISTINCT id)', $notIn['column']);
        $this->assertEquals('NOT IN', $notIn['operator']);
        $this->assertEquals(['1', '2'], $notIn['value']);

        $between = Expression::parse('COUNT(DISTINCT id) BETWEEN 1 AND 5');
        $this->assertEquals('COUNT(DISTINCT id)', $between['column']);
        $this->assertEquals('BETWEEN', $between['operator']);
        $this->assertEquals('(1 AND 5)', $between['value']);

        $like = Expression::parse("MAX(users.id) LIKE '%9'");
        $this->assertEquals('MAX(users.id)', $like['column']);
        $this->assertEquals('LIKE', $like['operator']);
        $this->assertEquals('%9', $like['value']);
    }

    public function testParsePlainColumnsAreUnaffected()
    {
        $this->assertEquals(
            ['column' => 'id', 'operator' => '>', 'value' => '3'], Expression::parse('id > 3')
        );
        $this->assertEquals(
            ['column' => 'users.id', 'operator' => '!=', 'value' => '3'], Expression::parse('users.id != 3')
        );
        // A space inside a quoted value is not a column boundary
        $this->assertEquals(
            ['column' => 'username', 'operator' => '=', 'value' => 'a = b'],
            Expression::parse("username = 'a = b'")
        );
    }

    public function testParseUnwrapsAnEnclosedExpression()
    {
        // A predicate set renders its predicates wrapped in parentheses
        $this->assertEquals(
            ['column' => 'id', 'operator' => '=', 'value' => '1'], Expression::parse('(id = 1)')
        );
        $this->assertEquals(
            ['column' => 'COUNT(DISTINCT id)', 'operator' => '>', 'value' => '1'],
            Expression::parse('(COUNT(DISTINCT id) > 1)')
        );

        // Two parenthesised expressions joined by AND are not a single wrapped expression, so
        // they are left alone and rejected by the operator whitelist
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        Expression::parse('(a = 1) AND (b = 2)');
    }

    public function testParseRejectsAFunctionCallTheWhitelistDoesNotAccept()
    {
        // Arithmetic in the argument list is not accepted by
        // AbstractSql::isSupportedFunctionCall(), and must be refused rather than handed to
        // quoteId() to come back as the bogus identifier `SUM(id + 1)`
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $this->expectExceptionMessage("The column 'SUM(id + 1)' is not a valid column name or supported function call");
        Expression::parse('SUM(id + 1) > 3');
    }

    public function testParseRejectsAnUnknownFunctionCall()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        Expression::parse('nope(id) > 3');
    }

    public function testParseDoesNotLetAnInjectionPayloadThroughAsAColumn()
    {
        // Regression for the case that drove the strict whole-value regex in
        // AbstractSql::isSupportedFunctionCall() - this must never parse into a column that
        // gets rendered verbatim
        $payloads = [
            'COUNT(1) OR 1=1 -- > 1',
            "COUNT(1),(SELECT password FROM admins) > 1",
            'COUNT(1 -- ) > 1',
            'COUNT(1 /*) > 1',
            "COUNT('a') > 1",
        ];

        foreach ($payloads as $payload) {
            $threw = false;
            try {
                $parsed = Expression::parse($payload);
                // If it parses at all, the column must not be waved through as a function call
                $this->assertFalse(
                    \Pop\Db\Sql\AbstractSql::isSupportedFunctionCall($parsed['column']),
                    $payload . ' parsed into a column that renders verbatim'
                );
            } catch (\Pop\Db\Sql\Parser\Exception $e) {
                $threw = true;
            }
            $this->assertTrue($threw, $payload . ' was expected to be rejected');
        }
    }

}
