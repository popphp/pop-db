<?php

namespace Pop\Db\Test\Adapter\Profiler;

use Pop\Db\Adapter\Profiler\Profiler;

class ProfilerTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $profiler = new Profiler();
        $this->assertInstanceOf('Pop\Db\Adapter\Profiler\Profiler', $profiler);
    }

    public function testGetStart()
    {
        $profiler = new Profiler();
        $this->assertGreaterThan(0, $profiler->getStart());
        $this->assertGreaterThan(0, $profiler->start);
    }

    public function testSetQuery()
    {
        $profiler = new Profiler();
        $profiler->setQuery('SELECT * FROM users');
        $this->assertTrue($profiler->hasQuery());
        $this->assertEquals('SELECT * FROM users', $profiler->getQuery());
        $this->assertEquals('SELECT * FROM users', $profiler->query);
    }

    public function testAddParams()
    {
        $profiler = new Profiler();
        $profiler->addParams([
            'foo' => 'bar',
            'baz' => 123
        ]);
        $this->assertTrue($profiler->hasParams());
        $this->assertEquals(2, count($profiler->getParams()));
        $this->assertEquals(2, count($profiler->params));
    }

    public function testAddError()
    {
        $profiler = new Profiler();
        $profiler->addError('Some error.', 1);
        $this->assertTrue($profiler->hasErrors());
        $this->assertEquals(1, count($profiler->getErrors()));
        $this->assertEquals(1, count($profiler->errors));
    }

    public function testFinish()
    {
        $profiler = new Profiler();
        sleep(1);
        $profiler->finish();
        $this->assertGreaterThan(0, $profiler->getFinish());
        $this->assertGreaterThan(0, $profiler->finish);
        $this->assertGreaterThan(0, $profiler->getElapsed());
    }

}