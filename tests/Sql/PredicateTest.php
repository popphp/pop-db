<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Predicate;
use PHPUnit\Framework\TestCase;

class PredicateTest extends TestCase
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
    
    public function testEqualTo()
    {
        $predicate = new Predicate\EqualTo(['username', 'admin']);
        $this->assertEquals('%1 = %2', $predicate->getFormat());
        $this->assertEquals('AND', $predicate->getConjunction());
        $predicate->setValues(['username', 'admin2']);
        $predicate->setConjunction('OR');
        $this->assertEquals('OR', $predicate->getConjunction());
        $this->assertEquals('username', $predicate->getValues()[0]);
        $this->assertEquals('admin2', $predicate->getValues()[1]);
        $this->assertEquals('OR', $predicate->getConjunction());
        $this->assertEquals("(`username` = 'admin2')", $predicate->render($this->db->createSql()));
    }

    public function testEqualToException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\EqualTo(['username']);
        $predicate->render($this->db->createSql());
    }

    public function testConjunctionException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\EqualTo(['username' => 'admin']);
        $predicate->setConjunction('BAD');
    }

    public function testNotEqualTo()
    {
        $predicate = new Predicate\NotEqualTo(['username', 'admin']);
        $this->assertEquals("(`username` != 'admin')", $predicate->render($this->db->createSql()));
    }

    public function testNotEqualToException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\NotEqualTo(['username']);
        $predicate->render($this->db->createSql());
    }

    public function testBetween()
    {
        $predicate = new Predicate\Between(['attempts', 1, 10]);
        $this->assertEquals("(`attempts` BETWEEN 1 AND 10)", $predicate->render($this->db->createSql()));
    }

    public function testBetweenException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\Between(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testNotBetween()
    {
        $predicate = new Predicate\NotBetween(['attempts', 1, 10]);
        $this->assertEquals("(`attempts` NOT BETWEEN 1 AND 10)", $predicate->render($this->db->createSql()));
    }

    public function testNotBetweenException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\NotBetween(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testGreaterThan()
    {
        $predicate = new Predicate\GreaterThan(['attempts', 10]);
        $this->assertEquals("(`attempts` > 10)", $predicate->render($this->db->createSql()));
    }

    public function testGreaterThanException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\GreaterThan(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testGreaterThanOrEqualTo()
    {
        $predicate = new Predicate\GreaterThanOrEqualTo(['attempts', 10]);
        $this->assertEquals("(`attempts` >= 10)", $predicate->render($this->db->createSql()));
    }

    public function testGreaterThanOrEqualToException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\GreaterThanOrEqualTo(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testLessThan()
    {
        $predicate = new Predicate\LessThan(['attempts', 10]);
        $this->assertEquals("(`attempts` < 10)", $predicate->render($this->db->createSql()));
    }

    public function testLessThanException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\LessThan(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testLessThanOrEqualTo()
    {
        $predicate = new Predicate\LessThanOrEqualTo(['attempts', 10]);
        $this->assertEquals("(`attempts` <= 10)", $predicate->render($this->db->createSql()));
    }

    public function testLessThanOrEqualToException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\LessThanOrEqualTo(['attempts', 1, 10, 15]);
        $predicate->render($this->db->createSql());
    }

    public function testIn()
    {
        $predicate = new Predicate\In(['attempts', [1, 10]]);
        $this->assertEquals("(`attempts` IN (1, 10))", $predicate->render($this->db->createSql()));
    }

    public function testInValuesException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\In(['attempts']);
        $predicate->render($this->db->createSql());
    }

    public function testInArrayException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\In(['attempts', 10]);
        $predicate->render($this->db->createSql());
    }

    public function testNotIn()
    {
        $predicate = new Predicate\NotIn(['attempts', [1, 10]]);
        $this->assertEquals("(`attempts` NOT IN (1, 10))", $predicate->render($this->db->createSql()));
    }

    public function testNotInValuesException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\NotIn(['attempts']);
        $predicate->render($this->db->createSql());
    }

    public function testNotInArrayException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\NotIn(['attempts', 10]);
        $predicate->render($this->db->createSql());
    }

    public function testIsNull()
    {
        $predicate = new Predicate\IsNull('logins');
        $this->assertEquals("(`logins` IS NULL)", $predicate->render($this->db->createSql()));
    }

    public function testIsNotNull()
    {
        $predicate = new Predicate\IsNotNull('logins');
        $this->assertEquals("(`logins` IS NOT NULL)", $predicate->render($this->db->createSql()));
    }

    public function testLike()
    {
        $predicate = new Predicate\Like(['username', 'admin%']);
        $this->assertEquals("(`username` LIKE 'admin%')", $predicate->render($this->db->createSql()));
    }

    public function testLikeException()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\Like(['username']);
        $predicate->render($this->db->createSql());
    }

    public function testNotLike()
    {
        $predicate = new Predicate\NotLike(['username', 'admin%']);
        $this->assertEquals("(`username` NOT LIKE 'admin%')", $predicate->render($this->db->createSql()));
    }

    public function testNotLikeEx()
    {
        $this->expectException('Pop\Db\Sql\Predicate\Exception');
        $predicate = new Predicate\NotLike(['username']);
        $predicate->render($this->db->createSql());
        $this->db->disconnect();
    }

}