<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;

class PdoTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorDbNotPassedException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo([]);
    }

    public function testConstructor()
    {
        Db::install(__DIR__ . '/../tmp/db.sql', [
            'database' => __DIR__  . '/../tmp/db.sqlite',
            'prefix'   => 'ph_',
            'type'     => 'sqlite'
        ], 'Pdo');

        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $adapters = $db->getAvailableAdapters();
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertContains('sqlite:', $db->getDsn());
        $this->assertContains('PDO', $db->version());
    }

    public function testExecuteException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->showError();
    }

    public function testShowErrorCodeAndInfo()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->showError(1001, 'Some Info');
    }

    public function testShowErrorCodeAndInfoArray()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->showError(1001, [1 => 'Some Info', 2 => 'Other Info']);
    }

    public function testIsInstalled()
    {
        $this->assertTrue(Pdo::isInstalled());
        $this->assertTrue(Pdo::isInstalled('sqlite'));
    }

    public function testLoadTables()
    {
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->prepare('INSERT INTO ph_users (username, password, email) VALUES (:username, :password, :email)')
           ->bindParams(['username' => $db->escape('testuser'), 'password' => '12test34', 'email' => 'test#test.com'])
           ->execute();

        $this->assertNotNull($db->lastId());

        $db->prepare('SELECT * FROM ph_users WHERE username = :username', [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY])
           ->bindParams(['username' => 'testuser'])
           ->execute();

        $rows = $db->fetchResult();

        $this->assertEquals(1, count($rows));

        $this->assertFalse($db->hasResult());
        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getConnection());

    }

    public function testQuery()
    {
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->query('SELECT * FROM ph_users');

        $rows = [];
        while (($row = $db->fetch())) {
            $rows[] = $row;
        }

        $this->assertEquals(1, count($rows));

        $this->assertNotNull($db->getResult());
        $this->assertEquals(0, $db->numberOfRows());
        $this->assertEquals(6, $db->numberOfFields());
        $db->disconnect();

        if (file_exists(__DIR__  . '/../tmp/db.sqlite')) {
            unlink(__DIR__  . '/../tmp/db.sqlite');
        }
    }

}