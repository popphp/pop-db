<?php

namespace Pop\Db\Test\Adapter\Profiler;

use Pop\Db\Adapter\Profiler\Step;
use PHPUnit\Framework\TestCase;

class StepTest extends TestCase
{

    public function testConstructor()
    {
        $step = new Step();
        $this->assertInstanceOf('Pop\Db\Adapter\Profiler\Step', $step);
    }

    public function testGetStart()
    {
        $step = new Step();
        $this->assertGreaterThan(0, $step->getStart());
        $this->assertGreaterThan(0, $step->start);
    }

    public function testSetQuery()
    {
        $step = new Step();
        $step->setQuery('SELECT * FROM users');
        $this->assertTrue($step->hasQuery());
        $this->assertEquals('SELECT * FROM users', $step->getQuery());
        $this->assertEquals('SELECT * FROM users', $step->query);
    }

    public function testAddParams()
    {
        $step = new Step();
        $step->addParams([
            'foo' => 'bar',
            'baz' => 123
        ]);
        $this->assertTrue($step->hasParams());
        $this->assertEquals(2, count($step->getParams()));
        $this->assertEquals(2, count($step->params));
    }

    public function testAddError()
    {
        $step = new Step();
        $step->addError('Some error.', 1);
        $this->assertTrue($step->hasErrors());
        $this->assertEquals(1, count($step->getErrors()));
        $this->assertEquals(1, count($step->errors));
    }

    public function testFinish()
    {
        $step = new Step();
        sleep(1);
        $step->finish();
        $this->assertGreaterThan(0, $step->getFinish());
        $this->assertGreaterThan(0, $step->finish);
        $this->assertGreaterThan(0, $step->getElapsed());
        $this->assertGreaterThan(0, $step->elapsed);
        $this->assertNull($step->foo);
    }

}