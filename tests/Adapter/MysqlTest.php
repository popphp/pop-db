<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Adapter\Profiler\Profiler;
use Pop\Db\Db;
use Pop\Db\Adapter\Mysql;
use PHPUnit\Framework\TestCase;
use Pop\Utils\CallableObject;
use Pop\Db\Test\TestAsset\TestQueryListener;
use Pop\Db\Test\TestAsset\StreamTestTrait;
use Pop\Db\Adapter\AbstractAdapter;

class MysqlTest extends TestCase
{

    use StreamTestTrait;

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

        $profiler = $db->listen(TestQueryListener::class, ['name' => 'query-listener'], new Profiler());

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

    public function testListenWithScalarParamsAndClassNameListener()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $listener = $db->listen(TestQueryListener::class, 'query-listener');
        $this->assertEquals('query-listener', $listener->getName());
        $db->disconnect();
    }

    public function testListenWithScalarParamsAndCallableObjectListener()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $listener = $db->listen(new CallableObject(TestQueryListener::class), 'query-listener');
        $this->assertEquals('query-listener', $listener->getName());
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
        $profiler = $db->listen(new CallableObject(TestQueryListener::class));

        $sql->insert()->into('users')->values([
            'username' => '?',
            'password' => '?',
            'email'    => '?'
        ]);

        $db->prepare($sql)
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());
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
        $this->assertNull($db->getError());
        $this->assertTrue($db->isSuccess());

        $db->disconnect();
    }

    public function testSelect()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $rows = $db->select('SELECT * FROM users WHERE id != ?', [0]);
        $this->assertTrue($db->hasStatement());
        $this->assertInstanceOf('mysqli_stmt', $db->getStatement());
        $this->assertEquals(1, count($rows));
        $this->assertNull($db->getError());

        $db->disconnect();
    }

    public function testSelectException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $rows = $db->select('BAD QUERY');
    }

    public function testInsert()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $result = $db->insert("INSERT INTO users (`username`, `password`, `email`) VALUES ('testuser_update', '12test34', 'test@test.com')");
        $this->assertEquals(1, $result);
        $db->disconnect();
    }

    public function testInsertException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $rows = $db->insert('BAD QUERY');
    }

    public function testUpdate()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $result = $db->update("UPDATE users SET `password` = '56test78' WHERE `username` = 'testuser_update'");
        $this->assertEquals(1, $result);
        $db->disconnect();
    }

    public function testUpdateException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $rows = $db->update('BAD QUERY');
    }

    public function testDelete()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $result = $db->delete("DELETE FROM users  WHERE `username` = 'testuser_update'");
        $this->assertEquals(1, $result);
        $db->disconnect();
    }

    public function testDeleteException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $rows = $db->delete('BAD QUERY');
    }

    public function testTransaction1()
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

    public function testTransaction2()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->transaction(function() use ($db) {
            $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
                ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
                ->execute();
        });

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(3, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testTransaction3Exception()
    {
        $this->expectException('Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->transaction(function() use ($db) {
            $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
                ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
                ->execute();
            throw new \Exception('Error: Test error');
        });
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

        $this->assertEquals(3, $db->getNumberOfRows());
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
        $this->assertTrue($db->isTransaction());
        $db->prepare('INSERT INTO users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->commit(MYSQLI_TRANS_COR_AND_CHAIN);

        $db->prepare('SELECT * FROM users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(4, $db->getNumberOfRows());
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

        $this->assertEquals(4, $db->getNumberOfRows());
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

        $this->assertEquals(5, $db->getNumberOfRows());
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

        $this->assertEquals(5, $db->getNumberOfRows());
        $db->disconnect();
    }

    public function testTransactionAfterSingleRowReadRollsBack()
    {
        $db = $this->createTxnThingsTable();

        $db->prepare('SELECT * FROM txn_things WHERE id = ?')->bindParams(['id' => 1])->execute();
        $this->assertEquals('orig', $db->fetch()['name']);

        $db->beginTransaction();
        $db->prepare('UPDATE txn_things SET name = ? WHERE id = ?')
           ->bindParams(['name' => 'changed', 'id' => 1])
           ->execute();
        $db->rollback();

        $db->query('SELECT name FROM txn_things WHERE id = 1');
        $this->assertEquals('orig', $db->fetch()['name']);

        $db->query('DROP TABLE txn_things');
        $db->disconnect();
    }

    public function testCommitAfterSingleRowReadInsideTransaction()
    {
        $db = $this->createTxnThingsTable();

        $db->beginTransaction();
        $db->prepare('UPDATE txn_things SET name = ? WHERE id = ?')
           ->bindParams(['name' => 'changed', 'id' => 1])
           ->execute();
        $db->prepare('SELECT * FROM txn_things WHERE id = ?')->bindParams(['id' => 2])->execute();
        $this->assertEquals('orig2', $db->fetch()['name']);
        $db->commit();
        $this->assertFalse($db->isTransaction());

        $db->query('SELECT name FROM txn_things WHERE id = 1');
        $this->assertEquals('changed', $db->fetch()['name']);

        $db->query('DROP TABLE txn_things');
        $db->disconnect();
    }

    public function testBeginTransactionFailureThrows()
    {
        $db = $this->createTxnThingsTable();
        $db->query('DROP TABLE txn_things');
        $this->killConnection($db);

        try {
            $db->beginTransaction();
            $this->fail('A failed BEGIN did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('Transaction Error', $e->getMessage());
        }

        $this->assertFalse($db->isTransaction());
        $this->assertEquals(0, $db->getTransactionDepth());
    }

    public function testCommitAndRollbackFailureThrow()
    {
        $db = $this->createTxnThingsTable();
        $db->query('DROP TABLE txn_things');
        $db->beginTransaction();
        $this->killConnection($db);

        try {
            $db->commit();
            $this->fail('A failed COMMIT did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('Transaction Error', $e->getMessage());
        }

        $this->assertTrue($db->isTransaction());

        try {
            $db->rollback();
            $this->fail('A failed ROLLBACK did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('Transaction Error', $e->getMessage());
        }

        $this->assertFalse($db->isTransaction());
    }

    public function testEscapeKeepsZero()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $this->assertSame('0', $db->escape('0'));
        $this->assertSame('', $db->escape(''));
        $this->assertSame('', $db->escape(null));
        $this->assertSame("O\\'Brien", $db->escape("O'Brien"));
        $db->disconnect();
    }

    public function testQueryException()
    {
        // mysqli throws mysqli_sql_exception on error by default since PHP 8.1 - Mysql::query()
        // must catch it and surface it as the standard adapter exception, like every other driver
        $this->expectException('Pop\Db\Adapter\Exception');

        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->query('SELECT * FROM `bad_table`');
    }

    public function testQueryExceptionRecordsProfilerError()
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->setProfiler(new Profiler());

        try {
            $db->query('SELECT * FROM `bad_table`');
            $this->fail('Exception was not thrown');
        } catch (\Pop\Db\Adapter\Exception) {
            // expected
        }

        $this->assertTrue($db->getProfiler()->current->hasErrors());
        $db->disconnect();
    }

    public function testExecuteException2()
    {
        // mysqli_stmt::execute() also throws mysqli_sql_exception by default since PHP 8.1
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

    public function testPrepareException()
    {
        // mysqli_stmt::prepare() also throws mysqli_sql_exception on bad syntax since PHP 8.1
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $db->prepare('SELEKT * FROM `bad syntax`');
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

    protected function createStreamAdapter(): AbstractAdapter
    {
        return new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
    }

    protected function streamParamQuery(): array
    {
        return ['SELECT * FROM stream_items WHERE id > ? ORDER BY id', [40]];
    }

    public function testStreamSecondQueryMidLoopThrows()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $stream = $db->stream('SELECT * FROM stream_items ORDER BY id');
        try {
            foreach ($stream as $row) {
                $db->query('SELECT 1');
            }
            $this->fail('A second query during the stream did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('2014', $e->getMessage());
        }
        unset($stream);

        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamMemoryIsBounded()
    {
        $db     = $this->createStreamAdapter();
        $digits = '(SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 ' .
            'UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9)';
        $sql    = "SELECT a.d + 10 * b.d + 100 * c.d + 1000 * e.d + 10000 * f.d AS n, REPEAT('x', 200) AS pad " .
            "FROM {$digits} a, {$digits} b, {$digits} c, {$digits} e, {$digits} f";

        $before = memory_get_usage();
        $peak   = $before;
        $count  = 0;
        foreach ($db->stream($sql) as $row) {
            $count++;
            $peak = max($peak, memory_get_usage());
        }

        $this->assertEquals(100000, $count);
        $this->assertLessThan(2 * 1024 * 1024, $peak - $before);
        $db->disconnect();
    }

    protected function createTxnThingsTable(): Mysql
    {
        $db = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $db->query('DROP TABLE IF EXISTS txn_things');
        $db->query('CREATE TABLE txn_things (id INT PRIMARY KEY, name VARCHAR(50)) ENGINE=InnoDB');
        $db->query("INSERT INTO txn_things VALUES (1, 'orig'), (2, 'orig2')");

        return $db;
    }

    protected function killConnection(Mysql $db): void
    {
        $killer = new Mysql([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
        $killer->query('KILL ' . $db->getConnection()->thread_id);
        $killer->disconnect();
    }

}
