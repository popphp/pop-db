<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Operator;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{

    public function testParseGreaterThanOrEqual()
    {
        $parsed = Operator::parse('id>=');
        $this->assertEquals('id', $parsed['column']);
        $this->assertEquals('>=', $parsed['operator']);
    }

    public function testParseLessThanOrEqual()
    {
        $parsed = Operator::parse('id<=');
        $this->assertEquals('id', $parsed['column']);
        $this->assertEquals('<=', $parsed['operator']);
    }

    public function testParseGreaterThan()
    {
        $parsed = Operator::parse('id>');
        $this->assertEquals('id', $parsed['column']);
        $this->assertEquals('>', $parsed['operator']);
    }

    public function testParseLessThan()
    {
        $parsed = Operator::parse('id<');
        $this->assertEquals('id', $parsed['column']);
        $this->assertEquals('<', $parsed['operator']);
    }

    public function testParseNotEqual()
    {
        $parsed = Operator::parse('id!=');
        $this->assertEquals('id', $parsed['column']);
        $this->assertEquals('!=', $parsed['operator']);
    }

}