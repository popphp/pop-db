<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Predicate;
use Pop\Db\Sql\PredicateSet;
use PHPUnit\Framework\TestCase;

class PredicateSetTest extends TestCase
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

    public function testConstructor()
    {
        $predicateSet = new PredicateSet($this->db->createSql(), new Predicate\EqualTo(['username', 'admin']), 'OR');
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', $predicateSet);
        $this->assertEquals('OR', $predicateSet->getConjunction());
        $predicateSet->setConjunction('AND');
        $this->assertEquals('AND', $predicateSet->getConjunction());
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
    }

    public function testParameters()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->setParameters(['foo' => 'bar']);
        $predicateSet->addParameters(['baz' => 'qux']);
        $this->assertTrue($predicateSet->hasParameters());
        $this->assertTrue($predicateSet->hasParameter('foo'));
        $this->assertEquals('qux', $predicateSet->getParameter('baz'));
    }

    public function testConstructorWithArray()
    {
        $predicateSet = new PredicateSet($this->db->createSql(), [
            new Predicate\EqualTo(['username', 'admin']),
            new Predicate\EqualTo(['email', 'admin@admin.com']),
        ]);
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', $predicateSet);
        $this->assertEquals(2, count($predicateSet->getPredicates()));
    }

    public function testSetConjunctionException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $predicateSet = new PredicateSet($this->db->createSql(), new Predicate\EqualTo(['username', 'admin']));
        $predicateSet->setNextConjunction('BAD');
    }

    public function testSetNextConjunctionException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $predicateSet = new PredicateSet($this->db->createSql(), new Predicate\EqualTo(['username', 'admin']));
        $predicateSet->setConjunction('BAD');
    }

    public function testAndPredicate()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->andPredicate(new Predicate\EqualTo(['username', 'admin']));
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertEquals('AND', $predicateSet->getPredicates()[0]->getConjunction());
    }

    public function testOrPredicate()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->orPredicate(new Predicate\EqualTo(['username', 'admin']));
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertEquals('OR', $predicateSet->getPredicates()[0]->getConjunction());
    }

    public function testAddPredicateSet()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->addPredicateSet(new PredicateSet($this->db->createSql()));
        $this->assertTrue($predicateSet->hasPredicateSets());
    }

    public function testAddPredicateSets()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->addPredicateSets([
            new PredicateSet($this->db->createSql()),
            new PredicateSet($this->db->createSql())
        ]);
        $this->assertTrue($predicateSet->hasPredicateSets());
        $this->assertEquals(2, count($predicateSet->getPredicateSets()));
    }

    public function testNest()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $nestedPredicateSet = $predicateSet->nest();
        $this->assertTrue($predicateSet->hasPredicateSets());
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', $nestedPredicateSet);
    }

    public function testAndNest()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $nestedPredicateSet = $predicateSet->andNest();
        $this->assertTrue($predicateSet->hasPredicateSets());
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', $nestedPredicateSet);
        $this->assertEquals('AND', $nestedPredicateSet->getConjunction());
    }

    public function testOrNest()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $nestedPredicateSet = $predicateSet->orNest();
        $this->assertTrue($predicateSet->hasPredicateSets());
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', $nestedPredicateSet);
        $this->assertEquals('OR', $nestedPredicateSet->getConjunction());
    }

    public function testAddEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("username = 'admin'");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\EqualTo', $predicateSet->getPredicates()[0]);
    }

    public function testAddNotEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("username != 'admin'");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\NotEqualTo', $predicateSet->getPredicates()[0]);
    }

    public function testAddGreaterThan()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts > 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\GreaterThan', $predicateSet->getPredicates()[0]);
    }

    public function testAddGreaterThanOrEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts >= 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\GreaterThanOrEqualTo', $predicateSet->getPredicates()[0]);
    }

    public function testAddLessThan()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts < 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\LessThan', $predicateSet->getPredicates()[0]);
    }

    public function testAddLessThanOrEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts <= 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\LessThanOrEqualTo', $predicateSet->getPredicates()[0]);
    }

    public function testAddLike()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("username LIKE 'admin%'");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\Like', $predicateSet->getPredicates()[0]);
    }

    public function testAddNotLike()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("username NOT LIKE 'admin%'");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\NotLike', $predicateSet->getPredicates()[0]);
    }

    public function testAddBetween()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts BETWEEN 5 AND 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\Between', $predicateSet->getPredicates()[0]);
    }

    public function testAddNotBetween()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts NOT BETWEEN 5 AND 10");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\NotBetween', $predicateSet->getPredicates()[0]);
    }

    public function testAddIn()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts IN (5, 10)");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\In', $predicateSet->getPredicates()[0]);
    }

    public function testAddNotIn()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts NOT IN (5, 10)");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\NotIn', $predicateSet->getPredicates()[0]);
    }

    public function testAddInWithPlaceholderBindsSingleArrayParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add('attempts IN (5, 10)', true);
        $this->assertEquals('(`attempts` IN (?, ?))', $predicateSet->render());
        $this->assertEquals(['attempts' => ['5', '10']], $predicateSet->getParameters());
    }

    public function testAddNotInWithPlaceholderBindsSingleArrayParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add('attempts NOT IN (5, 10)', true);
        $this->assertEquals('(`attempts` NOT IN (?, ?))', $predicateSet->render());
        $this->assertEquals(['attempts' => ['5', '10']], $predicateSet->getParameters());
    }

    public function testAddWithPlaceholderOnPgsqlBindsNumberedToken()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $predicateSet = new PredicateSet($db->createSql());
        $predicateSet->add("username = 'admin'", 1);
        $this->assertEquals('("username" = $1)', $predicateSet->render());
        $this->assertEquals(['username' => 'admin'], $predicateSet->getParameters());
        $db->disconnect();
    }

    public function testAddWithPlaceholderOnSqliteBindsNamedToken()
    {
        touch(__DIR__ . '/../tmp/predicate_set_add.sqlite');
        $db  = Db::sqliteConnect(['database' => __DIR__ . '/../tmp/predicate_set_add.sqlite']);
        $predicateSet = new PredicateSet($db->createSql());
        $predicateSet->add("username = 'admin'", true);
        $this->assertEquals('("username" = :username)', $predicateSet->render());
        $this->assertEquals(['username' => 'admin'], $predicateSet->getParameters());
        $db->disconnect();
        @unlink(__DIR__ . '/../tmp/predicate_set_add.sqlite');
    }

    public function testExtractValuesUsesParameterOverrideKeyedByPredicateIndex()
    {
        // extractValues()'s parameter lookup is keyed by the predicate's array index,
        // not by column name - addParameter() must be called with that literal index
        // for the override branch to apply (add()'s own placeholder binding keys its
        // parameters by column name, so it never reaches this branch in practice)
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->equalTo('username', 'admin');
        $predicateSet->addParameter(0, 'admin-override');
        $this->assertEquals(['username' => 'admin-override'], $predicateSet->extractValues());
    }

    public function testSplitBetweenValueReturnsValueUnchangedWhenNotAndShaped()
    {
        $method = new \ReflectionMethod(PredicateSet::class, 'splitBetweenValue');
        $this->assertEquals('no-and-here', $method->invoke(null, 'no-and-here'));
        $this->assertEquals(5, $method->invoke(null, 5));
    }

    public function testAddNull()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts IS NULL");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\IsNull', $predicateSet->getPredicates()[0]);
    }

    public function testAddNotNull()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add("attempts IS NOT NULL");
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\IsNotNull', $predicateSet->getPredicates()[0]);
    }

    public function testAddExpressions()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->addExpressions([
            "username = 'admin'",
            "attempts IS NOT NULL"
        ]);
        $this->assertEquals(2, count($predicateSet->getPredicates()));
    }

    public function testAddAnd()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->and("username = 'admin'");
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertEquals('AND', $predicateSet->getNextConjunction());
    }

    public function testAddOr()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->or("username = 'admin'");
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertEquals('OR', $predicateSet->getNextConjunction());
    }

    public function testRender1()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->equalTo('username', 'admin')->andNest()
            ->equalTo('email', 'test@test.com')
            ->or()->greaterThan('attempts', 5);

        $this->assertEquals(
            "((`username` = 'admin') AND ((`email` = 'test@test.com') OR (`attempts` > 5)))", (string)$predicateSet
        );
    }

    public function testRender2()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->equalTo('username', 'admin');
        $this->assertEquals("(`username` = 'admin')", (string)$predicateSet);
    }

    public function testRenderException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $predicateSet = new PredicateSet($this->db->createSql());
        $nested = new PredicateSet($this->db->createSql());
        $nested->add("(`username` = 'admin')")->add("(`username` = 'admin')");
        $predicateSet->addPredicateSet($nested);
        $string = $predicateSet->render();
        $this->db->disconnect();
    }

    public function testRenderWithOnlyNestedPredicateSetsNoDanglingConjunction()
    {
        $sql = $this->db->createSql();

        $childA = new PredicateSet($sql);
        $childA->equalTo('role', 'admin');
        $childA->setConjunction('OR');

        $childB = new PredicateSet($sql);
        $childB->greaterThanOrEqualTo('age', '65');
        $childB->setConjunction('OR');

        $predicateSet = new PredicateSet($sql);
        $predicateSet->addPredicateSet($childA);
        $predicateSet->addPredicateSet($childB);

        $this->assertEquals("((`role` = 'admin') OR (`age` >= 65))", $predicateSet->render());
        $this->db->disconnect();
    }

    public function testGetParametersRecursesIntoNestedPredicateSets()
    {
        $sql = $this->db->createSql();

        $child = new PredicateSet($sql);
        $child->addParameter('role', 'admin');
        $child->setConjunction('OR');

        $predicateSet = new PredicateSet($sql);
        $predicateSet->addParameter('status', 'active');
        $predicateSet->addPredicateSet($child);

        $this->assertTrue($predicateSet->hasParameters());
        $this->assertEquals(['status' => 'active', 'role' => 'admin'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testHasParametersTrueWhenOnlyNestedSetHasParameters()
    {
        $sql = $this->db->createSql();

        $child = new PredicateSet($sql);
        $child->addParameter('role', 'admin');

        $predicateSet = new PredicateSet($sql);
        $predicateSet->addPredicateSet($child);

        $this->assertTrue($predicateSet->hasParameters());
        $this->db->disconnect();
    }

    public function testExists()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $subquery     = $this->db->createSql()->select('id')->from('banned_users');
        $predicateSet->exists($subquery);
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\Exists', $predicateSet->getPredicates()[0]);
    }

    public function testNotExists()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $subquery     = $this->db->createSql()->select('id')->from('banned_users');
        $predicateSet->notExists($subquery);
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\NotExists', $predicateSet->getPredicates()[0]);
    }

    public function testJsonEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonEqualTo('data', '$.name', 'admin');
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\JsonEqualTo', $predicateSet->getPredicates()[0]);
    }

    public function testJsonNotEqualTo()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonNotEqualTo('data', '$.name', 'admin');
        $this->assertTrue($predicateSet->hasPredicates());
        $this->assertEquals(1, count($predicateSet->getPredicates()));
        $this->assertInstanceOf('Pop\Db\Sql\Predicate\JsonNotEqualTo', $predicateSet->getPredicates()[0]);
    }

    /**
     * The containment candidate is a RAW value that gets json_encode()'d verbatim at render
     * time - it is never a bound-parameter placeholder token. These tests go through
     * PredicateSet::jsonContains() (NOT direct predicate construction) specifically because
     * that is the path that runs the predicate's values through addPredicate()'s
     * isParameter()/getParameter() normalization loop. Values that loosely "look like" a
     * placeholder to isParameter() - a boolean (any non-empty string == true), the literal
     * '?', a '$N'-shaped string, or an array containing any of those - used to be silently
     * rewritten into a dialect placeholder token before ever reaching render(), producing
     * json_encode('?') instead of json_encode(true) and, on PostgreSQL, desyncing the
     * parameter numbering of unrelated sibling predicates.
     */
    public function testJsonContainsBooleanCandidateIsNotTreatedAsParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonContains('data', '$.roles', true);
        $this->assertEquals("(JSON_CONTAINS(`data`, 'true', '\$.roles'))", $predicateSet->render());
    }

    public function testJsonContainsQuestionMarkCandidateIsNotTreatedAsParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonContains('data', '$.roles', '?');
        $this->assertEquals('(JSON_CONTAINS(`data`, \'\\"?\\"\', \'$.roles\'))', $predicateSet->render());
    }

    public function testJsonContainsDollarNumberCandidateIsNotTreatedAsParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonContains('data', '$.roles', '$1');
        $this->assertEquals('(JSON_CONTAINS(`data`, \'\\"$1\\"\', \'$.roles\'))', $predicateSet->render());
    }

    public function testJsonContainsArrayCandidateIsNotTreatedAsParameter()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonContains('data', '$.roles', [true, '?', 'admin']);
        $this->assertEquals(
            '(JSON_CONTAINS(`data`, \'[true,\\"?\\",\\"admin\\"]\', \'$.roles\'))', $predicateSet->render()
        );
    }

    public function testJsonContainsNumericCandidate()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->jsonContains('data', '$.ids', 5);
        $this->assertEquals("(JSON_CONTAINS(`data`, '5', '\$.ids'))", $predicateSet->render());
    }

    /**
     * The corrupted-candidate bug also consumed a PostgreSQL parameter number (getParameter()
     * calls incrementParameterCount()), which shifted the placeholder number of every
     * subsequent predicate in the same set out of sync with the actually-bound parameter list.
     * The sibling equalTo() below must therefore still render as $1, not $2.
     */
    public function testJsonContainsDoesNotConsumePgsqlParameterNumber()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $predicateSet = new PredicateSet($db->createSql());
        $predicateSet->jsonContains('data', '$.roles', true);
        $predicateSet->equalTo('id', '$1');

        $this->assertEquals(
            '((("data" #> \'{roles}\') @> \'true\'::jsonb) AND ("id" = $1))', $predicateSet->render()
        );
        $db->disconnect();
    }

    public function testRenderNestAddedBeforePredicate()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->nest()->equalTo('county_id', '?')->or()->isNull('county_id');
        $predicateSet->equalTo('service_id', '?');

        $this->assertEquals(
            "(((`county_id` = ?) OR (`county_id` IS NULL)) AND (`service_id` = ?))", (string)$predicateSet
        );
        $this->db->disconnect();
    }

    public function testRenderInterleavedPredicatesAndNests()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->equalTo('a', 1);
        $predicateSet->nest()->equalTo('b', 2)->equalTo('c', 3);
        $predicateSet->equalTo('d', 4);
        $predicateSet->nest()->equalTo('e', 5)->or()->equalTo('f', 6);

        $this->assertEquals(
            "((`a` = 1) AND ((`b` = 2) AND (`c` = 3)) AND (`d` = 4) AND ((`e` = 5) OR (`f` = 6)))",
            (string)$predicateSet
        );
        $this->db->disconnect();
    }

    public function testRenderOrNestBetweenPredicatesKeepsConjunctions()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->equalTo('a', 1);
        $predicateSet->orNest()->equalTo('b', 2)->equalTo('c', 3);
        $predicateSet->equalTo('d', 4);

        $this->assertEquals(
            "((`a` = 1) OR ((`b` = 2) AND (`c` = 3)) AND (`d` = 4))", (string)$predicateSet
        );
        $this->db->disconnect();
    }

    public function testGetParametersFollowsRenderOrder()
    {
        $predicateSet = new PredicateSet($this->db->createSql());
        $predicateSet->add('region_id = 3', true);
        $predicateSet->nest()->add('county_id = 5', true)->or()->isNull('county_id');
        $predicateSet->add('service_id = 7', true);

        $this->assertEquals(
            "((`region_id` = ?) AND ((`county_id` = ?) OR (`county_id` IS NULL)) AND (`service_id` = ?))",
            (string)$predicateSet
        );
        // add() parses the value out of the expression string, so it arrives as a string
        $this->assertSame(['region_id' => '3', 'county_id' => '5', 'service_id' => '7'], $predicateSet->getParameters());
        $this->db->disconnect();
    }

    public function testOrderedPredicatesReturnTheRightRows()
    {
        $this->db->query('DROP TABLE IF EXISTS pred_order_items');
        $this->db->query(
            'CREATE TABLE pred_order_items (id INT PRIMARY KEY, county_id INT NULL, service_id INT, ' .
            'a INT, b INT, c INT, d INT)'
        );
        $this->db->query(
            'INSERT INTO pred_order_items VALUES (1, 5, 7, 0, 0, 0, 0), (2, 7, 5, 0, 0, 0, 0), ' .
            '(3, NULL, NULL, 1, 0, 0, 0), (4, NULL, NULL, 0, 1, 1, 0)'
        );

        // Positional '?' parameters, bound in the order the predicates were added
        $select = $this->db->createSql()->select('id')->from('pred_order_items');
        $select->where->nest()->equalTo('county_id', '?')->or()->isNull('county_id');
        $select->where->equalTo('service_id', '?');
        $this->db->prepare((string)$select)->bindParams(['county_id' => 5, 'service_id' => 7])->execute();
        $this->assertSame([1], array_map(fn($row) => (int)$row['id'], $this->db->fetchAll()));

        // a OR (b AND c) AND d, as written: row 3 matches; rendered out of order it would be row 4
        $select = $this->db->createSql()->select('id')->from('pred_order_items');
        $select->where->equalTo('a', 1);
        $select->where->orNest()->equalTo('b', 1)->equalTo('c', 1);
        $select->where->equalTo('d', 1);
        $this->db->query((string)$select);
        $ids = [];
        while (($row = $this->db->fetch())) {
            $ids[] = (int)$row['id'];
        }
        $this->assertSame([3], $ids);

        $this->db->query('DROP TABLE pred_order_items');
        $this->db->disconnect();
    }

}
