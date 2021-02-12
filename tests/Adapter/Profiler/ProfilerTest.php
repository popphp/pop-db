<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Adapter\Profiler\Profiler;
use Pop\Db\Adapter\Profiler\Step;
use PHPUnit\Framework\TestCase;
use Pop\Db\Db;

class ProfilerTest extends TestCase
{

    public function testStep()
    {
        $step = new Step();
        $step->setQuery('SELECT * FROM users');
        $step->addError('Test Error');
        $this->assertTrue($step->hasQuery());
        $this->assertTrue($step->hasErrors());
        $this->assertEquals(1, count($step->getErrors()));
        $this->assertEquals('SELECT * FROM users', $step->query);
        $this->assertEquals(0, count($step->params));
        $this->assertEquals(1, count($step->errors));
        $this->assertTrue(is_numeric($step->start));
        $this->assertNull($step->finish);
        $this->assertTrue(is_numeric($step->elapsed));
        $this->assertNull($step->bad);

    }

    public function testMysqlProfiler()
    {
        $db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->setProfiler(new Profiler());
        $this->assertInstanceOf('Pop\Db\Adapter\Profiler\Profiler', $db->getProfiler());
        $db->clearProfiler();
        $this->assertNull($db->getProfiler());
    }

    public function testMagicMethods()
    {
        $profiler = new Profiler();
        $this->assertTrue(is_numeric($profiler->start));
        $this->assertNull($profiler->finish);
        $this->assertTrue(is_numeric($profiler->elapsed));
        $this->assertNull($profiler->bad);
    }

}
