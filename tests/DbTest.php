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
        $this->assertNull(Db::check('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']));
    }

    public function testCheckBadAdapter()
    {
        $this->assertNotNull(Db::check('badadapter', [
            'database' => 'baddb',
            'username' => 'root',
            'password' => '12root34'
        ]));
    }

    public function testCheckBadDb()
    {
        $this->assertNotNull(Db::check('mysql', [
            'database' => 'baddb',
            'username' => 'root',
            'password' => '12root34'
        ]));
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
    }

}