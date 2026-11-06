<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Sql;
use Pop\Db\Adapter\Pgsql;
use PHPUnit\Framework\TestCase;
use Pop\Db\Test\TestAsset\TestQueryListener;
use Pop\Db\Test\TestAsset\StreamTestTrait;
use Pop\Db\Adapter\AbstractAdapter;

class PgsqlTest extends TestCase
{

    use StreamTestTrait;

    protected function createStreamAdapter(): AbstractAdapter
    {
        return Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
    }

    protected function streamParamQuery(): array
    {
        return ['SELECT * FROM stream_items WHERE id > $1 ORDER BY id', [40]];
    }

    public function testStreamBreakEarlyInsideCallerTransactionLeavesItUsable()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->beginTransaction();
        $db->query("UPDATE stream_items SET name = 'changed' WHERE id = 1");

        // pg_sleep() keeps the server producing rows, so the query is still running when the loop stops
        $stream = $db->stream('SELECT g FROM generate_series(1, 1500) g WHERE pg_sleep(0.001) IS NOT NULL');
        foreach ($stream as $row) {
            break;
        }
        unset($stream);

        // The caller's transaction is still usable, and its earlier write is still in it
        $db->query('SELECT name FROM stream_items WHERE id = 1');
        $this->assertEquals('changed', $this->fetchAllRows($db)[0]['name']);
        $this->assertTrue($db->isTransaction());
        $db->commit();

        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamSecondQueryMidLoopThrows()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        // pg_query() discards the stream's pending rows (raising only an E_NOTICE) and runs, so the
        // stream itself must report the interruption instead of ending early with rows missing
        $stream = $db->stream('SELECT g AS id FROM generate_series(1, 2500) g ORDER BY g');
        $seen   = 0;
        set_error_handler(fn() => true, E_NOTICE);
        try {
            foreach ($stream as $row) {
                $seen++;
                $db->query('SELECT 1');
            }
            $this->fail('A second query during the stream went unreported');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('interrupted', $e->getMessage());
        } finally {
            restore_error_handler();
        }
        $this->assertLessThan(2500, $seen);
        unset($stream);

        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

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

    public function testEscapeKeepsZero()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $this->assertSame('0', $db->escape('0'));
        $this->assertSame('', $db->escape(''));
        $this->assertSame('', $db->escape(null));
        $this->assertSame("O''Brien", $db->escape("O'Brien"));
        $db->disconnect();
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

        $profiler = $db->listen(TestQueryListener::class);

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
        $this->assertInstanceOf('Pop\Db\Adapter\Pgsql', $db);
        $this->assertStringContainsString('PostgreSQL', $db->getVersion());
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

        $profiler = $db->listen(TestQueryListener::class);

        $db->beginTransaction();

        $db->prepare('INSERT INTO users ("username", "password", "email") VALUES ($1, $2, $3)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $db->commit();
        $this->assertEquals(0, $db->getNumberOfAffectedRows());

        $this->assertTrue($db->hasResult());
        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());
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
