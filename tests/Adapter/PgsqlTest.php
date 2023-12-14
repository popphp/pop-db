<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Sql;
use Pop\Db\Adapter\Pgsql;
use PHPUnit\Framework\TestCase;

class PgsqlTest extends TestCase
{

    public function testConstructorException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS']
        ]);
    }

    public function testConnectException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pgsql();
        $db->connect();
    }

    public function testPgsqlConnect()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pgsql', $db);
    }

    public function testConstructor()
    {
        $db = new Pgsql([
            'database'        => $_ENV['PGSQL_DB'],
            'username'        => $_ENV['PGSQL_USER'],
            'password'        => $_ENV['PGSQL_PASS'],
            'port'            => 5432,
            'hostaddr'        => '127.0.0.1',
            'connect_timeout' => 3000,
            'options'         => "'--client_encoding=UTF8'",
            'type'            => PGSQL_CONNECT_FORCE_NEW
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $db->query('DROP TABLE IF EXISTS "users" CASCADE');

        $schema = $db->createSchema();
        $schema->createIfNotExists('users')
            ->int('id', 16)->notNullable()->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->int('active', 1)
            ->int('verified', 1)
            ->primary('id');

        $this->assertFalse($db->hasTable('users'));
        $db->query($schema);

        $this->assertTrue($db->hasTable('users'));
        $debugResults = $profiler->prepareAsString();
        $this->assertInstanceOf('Pop\Db\Adapter\Pgsql', $db);
        $this->assertStringContainsString('PostgreSQL', $db->getVersion());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS "users"', $debugResults);
    }

    public function testExecuteException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $db->throwError('Error: Some Error');
    }

    public function testGetTables()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $this->assertContains('users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $db->beginTransaction();

        $db->prepare('INSERT INTO users ("username", "password", "email") VALUES ($1, $2, $3)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $db->commit();
        $this->assertEquals(0, $db->getNumberOfAffectedRows());

        $debugResults = $profiler->prepareAsString();

        $this->assertTrue($db->hasResult());
        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('INSERT INTO users', $debugResults);
    }

    public function testRollback()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $db->beginTransaction();

        $db->prepare('INSERT INTO users ("username", "password", "email") VALUES ($1, $2, $3)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();

        $db->rollback();
        $this->assertEquals(0, $db->getNumberOfRows());
    }

    public function testFetch()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $db->query('SELECT * FROM users');

        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testFetchResults()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $db->prepare('SELECT * FROM "users" WHERE id != $1')
           ->bindParams([0])
           ->execute();

        $rows = $db->fetchAll();
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testDropTable()
    {
        $db = new Pgsql([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $schema = $db->createSchema();
        $schema->drop('users');

        $this->assertTrue($db->hasTable('users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('users'));

        $db->disconnect();
    }

}
