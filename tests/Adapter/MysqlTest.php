<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Adapter\Profiler\Profiler;
use Pop\Db\Db;
use Pop\Db\Adapter\Mysql;
use PHPUnit\Framework\TestCase;
use Pop\Utils\CallableObject;

class MysqlTest extends TestCase
{

    public function testConstructorException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS']
        ]);
    }

    public function testConnectException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql();
        $db->connect();
    }

    public function testMysqlConnect()
    {
        $db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $db);
        $this->assertInstanceOf('Pop\Db\Sql', $db->createSql());
        $this->assertInstanceOf('Pop\Db\Sql\Schema', $db->createSchema());
        $this->assertIsArray($db->getOptions());
        $this->assertEquals('test_popdb', $db->getOptions()['database']);
        $db->disconnect();
    }

    public function testCreateTable()
    {
        $db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler', ['name' => 'query-listener'], new Profiler());

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
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $db);
        $this->assertStringContainsString('MySQL', $db->getVersion());
        $db->disconnect();
    }

    public function testExecuteException1()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->clearError();
        $this->assertFalse($db->hasError());
        $db->throwError('Error: Some Error');
    }

    public function testGetTables()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $this->assertContains('users', $db->getTables());
        $this->assertTrue($db->hasTable('users'));
        $db->disconnect();
    }

    public function testBindParams()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $sql      = $db->createSql();
        $profiler = $db->listen(new CallableObject('Pop\Debug\Handler\QueryHandler'));

        $sql->insert()->into('users')->values([
            'username' => '?',
            'password' => '?',
            'email'    => '?'
        ]);

        $db->prepare($sql)
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $debugResults = $profiler->prepareAsString();

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());
        $this->assertStringContainsString('Start:', $debugResults);
        $this->assertStringContainsString('Finish:', $debugResults);
        $this->assertStringContainsString('Elapsed:', $debugResults);
        $this->assertStringContainsString('INSERT INTO `users`', $debugResults);
        $db->disconnect();
    }

    public function testFetch()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
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
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->prepare('SELECT * FROM users WHERE id != ?')
           ->bindParams([0])
           ->execute();

        $rows = $db->fetchAll();
        $this->assertTrue($db->hasStatement());
        $this->assertInstanceOf('mysqli_stmt', $db->getStatement());
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertNull($db->getError());

        $db->disconnect();
    }

    public function testTransaction()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
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
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
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

    public function testTransactionWithFlags()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->beginTransaction(MYSQLI_TRANS_START_READ_WRITE);
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->commit(MYSQLI_TRANS_COR_AND_CHAIN);

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(3, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testRollbackFlags()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->beginTransaction(MYSQLI_TRANS_START_READ_WRITE);
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->rollback(MYSQLI_TRANS_COR_AND_CHAIN);

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(3, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testTransactionWithFlagsAndName()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->beginTransaction(MYSQLI_TRANS_START_READ_WRITE, 'test_trans');
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->commit(MYSQLI_TRANS_COR_AND_CHAIN, 'test_trans');

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(4, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testRollbackFlagsAndName()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->beginTransaction(MYSQLI_TRANS_START_READ_WRITE, 'test_trans');
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->rollback(MYSQLI_TRANS_COR_AND_CHAIN, 'test_trans');

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(4, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testQueryException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->query('SELECT * FROM `bad_table`');
    }

    public function testExecuteException2()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->prepare('SELECT * FROM `bad_table` WHERE `id` = ?')
            ->bindParams([1])
            ->execute();
    }

    public function testDropTable()
    {
        $db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $schema = $db->createSchema();
        $schema->drop('users');

        $this->assertTrue($db->hasTable('users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('users'));

        $db->disconnect();
    }

}
