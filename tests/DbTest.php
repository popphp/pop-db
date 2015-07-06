<?php

namespace Pop\Db\Test;

use Pop\Db\Db;

class DbTest extends \PHPUnit_Framework_TestCase
{

    public function testInstallBadAdapterException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        Db::install(__DIR__ . '/tmp/db.sql', ['database' => __DIR__  . '/tmp/db.sqlite'], 'badadapter');
        $this->assertFileExists(__DIR__  . '/tmp/db.sqlite');
    }

    public function testInstall()
    {
        Db::install(__DIR__ . '/tmp/db.sql', [
            'database' => __DIR__  . '/tmp/db.sqlite',
            'prefix'   => 'ph_'
        ], 'Sqlite');
        $this->assertFileExists(__DIR__  . '/tmp/db.sqlite');
    }

    public function testCheck()
    {
        $this->assertNull(Db::check(['database' => __DIR__  . '/tmp/db.sqlite'], 'Sqlite'));
    }

    public function testCheckBadAdapter()
    {
        $this->assertNotNull(Db::check([
            'database' => 'baddb',
            'username' => 'root',
            'password' => '12root34'
        ], 'badadapter'));
    }

    public function testCheckBadDb()
    {
        $this->assertNotNull(Db::check([
            'database' => 'baddb',
            'username' => 'root',
            'password' => '12root34'
        ], 'Mysql'));
    }

    public function testConnectException()
    {
        $this->setExpectedException('Pop\Db\Exception');
        $db = Db::connect('badadapter', ['database' => __DIR__  . '/tmp/db.sqlite']);
    }

    public function testConnect()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
        if (file_exists(__DIR__  . '/tmp/db.sqlite')) {
            unlink(__DIR__  . '/tmp/db.sqlite');
        }
    }

}