<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;

class PdoTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorDbNotPassedException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo([]);
    }

    public function testConstructor()
    {
        Db::install(__DIR__ . '/../tmp/db.sql', 'Pdo', [
            'database' => __DIR__  . '/../tmp/db.sqlite',
            'prefix'   => 'ph_',
            'type'     => 'sqlite'
        ]);

        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite', 'options' => [\PDO::ATTR_PERSISTENT => false]]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertContains('sqlite:', $db->getDsn());
        $this->assertContains('PDO', $db->getVersion());
    }

    public function testExecuteException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo(['database' => __DIR__  . '/../tmp/db.sqlite', 'type' => 'sqlite']);
        $db->throwError('Error: Some Error');
    }

    public function testGetTables()
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

        $this->assertNotNull($db->getLastId());

        $db->prepare('SELECT * FROM ph_users WHERE username = :username', [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY])
           ->bindParams(['username' => 'testuser'])
           ->execute();

        $rows = $db->fetchAll();

        $this->assertEquals(1, count($rows));
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
        $this->assertEquals(0, $db->getNumberOfRows());
        $db->disconnect();

        if (file_exists(__DIR__  . '/../tmp/db.sqlite')) {
            unlink(__DIR__  . '/../tmp/db.sqlite');
        }
    }

}