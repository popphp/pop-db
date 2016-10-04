<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Sqlite;

class SqliteTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorDbNotPassedException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite([]);
    }

    public function testConstructorDbDoesNotExistException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/bad.sqlite']);
    }

    public function testConstructor()
    {
        Db::install(__DIR__ . '/../tmp/db.sql', 'Sqlite', [
            'database' => __DIR__  . '/../tmp/db.sqlite',
            'prefix'   => 'ph_'
        ]);

        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
        $this->assertContains('SQLite', $db->getVersion());
    }

    public function testExecuteException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->throwError('Error: Some Error');
    }

    public function testGetTables()
    {
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Sqlite(['database' => __DIR__  . '/../tmp/db.sqlite']);
        $db->prepare('UPDATE ph_users SET email = :email WHERE id > :id')
           ->bindParams(['id' => 0])
           ->execute();

        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());

        $db->disconnect();

        if (file_exists(__DIR__  . '/../tmp/db.sqlite')) {
            unlink(__DIR__  . '/../tmp/db.sqlite');
        }
    }

}