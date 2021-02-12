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
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => '127.0.0.1'
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

}