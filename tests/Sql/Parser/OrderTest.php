<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{

    public function testParseAsc()
    {
        $parsed = Order::parse('id ASC');
        $this->assertEquals('id', $parsed['by']);
        $this->assertEquals('ASC', $parsed['order']);
    }

    public function testParseDesc()
    {
        $parsed = Order::parse('id DESC');
        $this->assertEquals('id', $parsed['by']);
        $this->assertEquals('DESC', $parsed['order']);
    }

    public function testParseRand()
    {
        $parsed = Order::parse('id RAND()');
        $this->assertEquals('id', $parsed['by']);
        $this->assertEquals('RAND()', $parsed['order']);
    }

    public function testParseNone()
    {
        $parsed = Order::parse('id');
        $this->assertEquals('id', $parsed['by']);
        $this->assertNull($parsed['order']);
    }

    public function testParseWithComma()
    {
        $parsed = Order::parse('id, count ASC');
        $this->assertTrue(is_array($parsed['by']));
        $this->assertEquals(2, count($parsed['by']));
        $this->assertEquals('id', $parsed['by'][0]);
        $this->assertEquals('count', $parsed['by'][1]);
        $this->assertEquals('ASC', $parsed['order']);
    }

}