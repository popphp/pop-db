<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{

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
        $this->assertEquals(2, count($components[6]['value']));
        $this->assertEquals('50', $components[6]['value'][0]);
        $this->assertEquals('100', $components[6]['value'][1]);
    }

    public function testParseException()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $component = Expression::parse("username <> 'admin'");
    }

}