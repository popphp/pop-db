<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;
use PHPUnit\Framework\TestCase;

class PdoSqliteTest extends TestCase
{
    public function setUp(): void
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);
    }

    public function testConstructorException1()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo([
            'db' => __DIR__ . '/../tmp/db.sqlite'
        ]);
    }

    public function testPdoConnect()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertStringContainsString('sqlite', $db->getDsn());
    }

    public function testAttributes()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);
        $db->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        $this->assertEquals(\PDO::CASE_NATURAL, $db->getAttribute(\PDO::ATTR_CASE));
    }

    public function testCreateTable()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('CREATE TABLE "users"', $debugResults);
    }

    public function testBindParams()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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
        $debugParams  = $db->debugDumpParams(true);

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertNotNull($db->getNumberOfFields());
        $this->assertNotNull($db->getCountOfFields());
        $this->assertNotNull($db->getCountOfRows());
        $this->assertNotNull($db->fetchColumn(1));
        $this->assertNotNull($db->closeCursor());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('INSERT INTO "users"', $debugResults);
        $this->assertStringContainsString('"username", "password", "email"', $debugParams);
    }

    public function testFetch()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);
        $db->query('SELECT * FROM users');
        $this->assertTrue($db->hasResult());
        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(0, $db->getNumberOfRows());
    }

    public function testFetchResults()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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
        $this->assertNull($db->getError());
    }

    public function testTransaction()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);

        $sql = $db->createSql();
        $sql->insert()->into('users')->values([
            'username' => ':username',
            'password' => ':password',
            'email'    => ':email'
        ]);

        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());

        $db->prepare($sql)
            ->bindParams([
                'username' => 'testuser2',
                'password' => '123456',
                'email'    => $db->escape('test2@test.com')
            ])->execute();

        $db->commit();

        $db->query('SELECT * FROM users');

        $this->assertEquals(0, $db->getNumberOfRows());
    }

    public function testRollback()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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

        $this->assertEquals(0, $db->getNumberOfRows());
    }

    public function testBindParam()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $username = 'testuser3';
        $db->prepare($sql)
            ->bindParam(':username', $username)->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
    }

    public function testBindValue()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
    }

    public function testError()
    {
        if (strpos(PHP_VERSION, '8.1') !== false) {
            $this->expectException('PDOException');
        } else {
            $this->expectException('Error');
        }

        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $sql = $db->createSql();

        $sql->select()->from('bad_table');
        $db->query($sql);
    }

    public function testDropTable()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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