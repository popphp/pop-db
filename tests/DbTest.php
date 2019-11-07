<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{

    public function testInstallBadAdapterException()
    {
        $this->expectException('Pop\Db\Exception');
        Db::install(__DIR__ . '/tmp/db.sql', 'badadapter', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $this->assertFileExists(__DIR__  . '/tmp/db.sqlite');
    }

    public function testInstall()
    {
        Db::install(__DIR__ . '/tmp/db.sql', 'Sqlite', [
            'database' => __DIR__  . '/tmp/db.sqlite',
            'prefix'   => 'ph_'
        ]);
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
        $this->expectException('Pop\Db\Exception');
        $db = Db::connect('badadapter', ['database' => __DIR__  . '/tmp/db.sqlite']);
    }

    public function testConnect()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
    }

    public function testSqliteConnect()
    {
        $db = Db::sqliteConnect(['database' => __DIR__  . '/tmp/db.sqlite']);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
    }

    public function testPdoConnect()
    {
        $db = Db::pdoConnect(['database' => __DIR__  . '/tmp/db.sqlite', 'type' => 'sqlite']);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
    }

    public function testAdapters()
    {
        $available = Db::getAvailableAdapters();
        $this->assertTrue($available['mysqli']);
        $this->assertTrue(Db::isAvailable('mysqli'));
        $this->assertTrue(Db::isAvailable('pdo_mysql'));
        $this->assertTrue(Db::isAvailable('pdo_pgsql'));
        $this->assertTrue(Db::isAvailable('pdo_sqlite'));
        $this->assertTrue(Db::isAvailable('sqlite'));
        $this->assertTrue(Db::isAvailable('pgsql'));
        $this->assertFalse(Db::isAvailable('sqlsrv'));
    }

}