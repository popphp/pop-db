<?php

namespace Pop\Db\Test\Adapter\Profiler;

use Pop\Db\Adapter\Profiler\Profiler;
use PHPUnit\Framework\TestCase;

class ProfilerTest extends TestCase
{

    public function testConstructor()
    {
        $profiler = new Profiler();
        $this->assertInstanceOf('Pop\Db\Adapter\Profiler\Profiler', $profiler);
        $this->assertNull($profiler->current);
    }

    public function testGetStart()
    {
        $profiler = new Profiler();
        $this->assertGreaterThan(0, $profiler->getStart());
        $this->assertGreaterThan(0, $profiler->start);
    }

    public function testAddStep()
    {
        $profiler = new Profiler();
        $profiler->addStep();
        $profiler->addStep();
        $this->assertEquals(2, count($profiler->getSteps()));
        $this->assertInstanceOf('Pop\Db\Adapter\Profiler\Step', $profiler->current);
    }

    public function testFinish()
    {
        $profiler = new Profiler();
        sleep(1);
        $profiler->finish();
        $this->assertGreaterThan(0, $profiler->getFinish());
        $this->assertGreaterThan(0, $profiler->finish);
        $this->assertGreaterThan(0, $profiler->getElapsed());
        $this->assertGreaterThan(0, $profiler->elapsed);
        $this->assertNull($profiler->foo);
    }

}