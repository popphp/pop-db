<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;
use PHPUnit\Framework\TestCase;

class PdoMysqlTest extends TestCase
{

    public function testConstructorException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo([
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'type'     => 'mysql'
        ]);
    }

    public function testConnectException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo();
        $db->connect();
    }

    public function testMysqlConnect()
    {
        $db = Db::pdoConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertInstanceOf('Pop\Db\Sql', $db->createSql());
        $this->assertInstanceOf('Pop\Db\Sql\Schema', $db->createSchema());
        $db->disconnect();
    }

    public function testCreateTable()
    {
        $db = Db::pdoConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
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

        $debugResults = $profiler->prepareAsString();
        $this->assertTrue($db->hasTable('users'));
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('CREATE TABLE `users`', $debugResults);
        $db->disconnect();
    }

    public function testConstructor()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);

        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertStringContainsString('PDO mysql', $db->getVersion());
        $db->disconnect();
    }

    public function testGetTables()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);
        $this->assertContains('users', $db->getTables());
        $this->assertTrue($db->hasTable('users'));
        $db->disconnect();
    }

    public function testBindParams()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
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
           ])
           ->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('INSERT INTO `users`', $debugResults);
        $db->disconnect();
    }

    public function testFetch()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);
        $db->query('SELECT * FROM users');
        $this->assertTrue($db->hasResult());
        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testFetchResults()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);
        $db->prepare('SELECT * FROM users WHERE id != ?')
           ->bindParams([0])
           ->execute();

        $rows = $db->fetchAll();
        $this->assertTrue($db->hasStatement());
        $this->assertInstanceOf('PDOStatement', $db->getStatement());
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertNull($db->getError());

        $db->disconnect();
    }

    public function testTransaction()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);

        $db->beginTransaction();
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();
        $db->commit();

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(2, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testRollback()
    {
        $db = new Pdo([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);

        $db->beginTransaction();
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->rollback();

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(2, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testDropTable()
    {
        $db = Db::pdoConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST'],
            'type'     => 'mysql'
        ]);

        $schema = $db->createSchema();
        $schema->drop('users');

        $this->assertTrue($db->hasTable('users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('users'));

        $db->disconnect();
    }

}
