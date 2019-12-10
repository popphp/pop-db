<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Sqlite;
use PHPUnit\Framework\TestCase;

class SqliteTest extends TestCase
{

    public function setUp()
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);
    }

    public function testConstructorException1()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite([
            'db' => __DIR__ . '/../tmp/db.sqlite'
        ]);
    }

    public function testConstructorException2()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite([
            'database' => __DIR__ . '/../tmp/bad.sqlite'
        ]);
    }

    public function testConstructorException3()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Sqlite();
        $db->connect();
    }

    public function testSqliteConnect()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
        $this->assertContains('SQLite', $db->getVersion());
    }

    public function testCreateTable()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $schema = $db->createSchema();
        $schema->create('users')
            ->int('id')->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $this->assertFalse($db->hasTable('users'));
        $db->query($schema);
        $this->assertTrue($db->hasTable('users'));

        $debugResults = $profiler->prepareAsString();
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
        $this->assertContains('CREATE TABLE "users"', $debugResults);
    }

    public function testBindParams()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $sql      = $db->createSql();
        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $sql->insert()->into('users')->values([
            'username' => ':username',
            'password' => ':password',
            'email'    => ':email'
        ]);

        $db->prepare($sql)
            ->bindParams([
                'username' => 'testuser',
                'password' => '12test34',
                'email'    => $db->escape('test@test.com')
            ])->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
        $this->assertContains('INSERT INTO "users"', $debugResults);
    }

    public function testFetch()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);
        $db->query('SELECT * FROM users');
        $this->assertTrue($db->hasResult());
        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testFetchResults()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $db->query('SELECT * FROM users');
        $this->assertTrue($db->hasResult());
        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }

        $userId = $rows[0]['id'];

        $db->prepare('SELECT * FROM users WHERE id = :id')
            ->bindParams(['id' => $userId])
            ->execute();

        $rows = $db->fetchAll();
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertNull($db->getError());
    }

    public function testTransaction()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $sql = $db->createSql();
        $sql->insert()->into('users')->values([
            'username' => ':username',
            'password' => ':password',
            'email'    => ':email'
        ]);

        $db->beginTransaction();
        $db->prepare($sql)
            ->bindParams([
                'username' => 'testuser2',
                'password' => '123456',
                'email'    => $db->escape('test2@test.com')
            ])->execute();

        $db->commit();

        $db->query('SELECT * FROM users');

        $this->assertEquals(2, $db->getNumberOfRows());
    }

    public function testRollback()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $sql = $db->createSql();
        $sql->insert()->into('users')->values([
            'username' => ':username',
            'password' => ':password',
            'email'    => ':email'
        ]);

        $db->beginTransaction();
        $db->prepare($sql)
            ->bindParams([
                'username' => 'testuser2',
                'password' => '123456',
                'email'    => $db->escape('test2@test.com')
            ])->execute();

        $db->rollback();

        $db->query('SELECT * FROM users');

        $this->assertEquals(2, $db->getNumberOfRows());
    }

    public function testBindParam()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $db->prepare($sql)
            ->bindParam(':username', 'testuser3')->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
    }

    public function testBindValue()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $db->prepare($sql)
            ->bindValue(':username', 'testuser3')->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
    }

    public function testDropTable()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $schema = $db->createSchema();
        $schema->drop('users');

        $this->assertTrue($db->hasTable('users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('users'));

        $db->disconnect();

        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

}
