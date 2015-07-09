<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Sqlite;

class SqliteTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorDbNotPassedException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Sqlite([]);
    }

    public function testConstructorDbDoesNotExistException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/bad.sqlite']);
    }

    public function testConstructor()
    {
        Db::install(__DIR__ . '/../tmp/db.sql', [
            'database' => __DIR__  . '/../tmp/db.sqlite',
            'prefix'   => 'ph_'
        ], 'Sqlite');

        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $adapters = $db->getAvailableAdapters();
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
        $this->assertContains('SQLite', $db->version());
        $this->assertTrue(isset($adapters['mysqli']));
        $this->assertTrue(is_bool($db->isAvailable('mysql')));
        $this->assertTrue(is_bool($db->isAvailable('oracle')));
        $this->assertTrue(is_bool($db->isAvailable('pgsql')));
        $this->assertTrue(is_bool($db->isAvailable('sqlsrv')));
        $this->assertTrue(is_bool($db->isAvailable('pdo_sqlite')));
    }

    public function testExecuteException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->showError();
    }

    public function testIsInstalled()
    {
        $this->assertTrue(Sqlite::isInstalled());
    }

    public function testLoadTables()
    {
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->prepare('UPDATE ph_users SET email = :email WHERE id > :id1 AND id < :id2')
           ->bindParams(['id' => [0, 10000]])
           ->execute();

        $this->assertFalse($db->hasResult());
        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->numberOfRows());
        $this->assertEquals(0, $db->numberOfFields());

        $db->disconnect();

        if (file_exists(__DIR__  . '/../tmp/db.sqlite')) {
            unlink(__DIR__  . '/../tmp/db.sqlite');
        }
    }

}