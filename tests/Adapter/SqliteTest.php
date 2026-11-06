<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Sqlite;
use PHPUnit\Framework\TestCase;
use Pop\Utils\CallableObject;
use Pop\Db\Test\TestAsset\TestQueryListener;
use Pop\Db\Test\TestAsset\StreamTestTrait;
use Pop\Db\Adapter\AbstractAdapter;

class SqliteTest extends TestCase
{

    use StreamTestTrait;

    public function setUp(): void
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);
    }

    public static function tearDownAfterClass(): void
    {
        // setUp() creates db.sqlite for every test, including the stream tests that run after
        // testDropTable() has removed it
        if (file_exists(__DIR__ . '/../tmp/db.sqlite')) {
            unlink(__DIR__ . '/../tmp/db.sqlite');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . '/../tmp/stream.sqlite')) {
            unlink(__DIR__ . '/../tmp/stream.sqlite');
        }
    }

    protected function createStreamAdapter(): AbstractAdapter
    {
        touch(__DIR__ . '/../tmp/stream.sqlite');
        return new Sqlite([
            'database' => __DIR__ . '/../tmp/stream.sqlite'
        ]);
    }

    protected function streamParamQuery(): array
    {
        return ['SELECT * FROM stream_items WHERE id > :id ORDER BY id', ['id' => 40]];
    }

    public function testStreamRuntimeErrorMidStreamThrows()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        // abs() of the minimum integer overflows at row 30, after 29 rows have been stepped
        $sql  = 'SELECT id, CASE WHEN id = 30 THEN abs(-9223372036854775807 - 1) ELSE id END AS v FROM stream_items';
        $ids  = [];
        set_error_handler(fn() => true, E_WARNING);
        try {
            foreach ($db->stream($sql) as $row) {
                $ids[] = $row['id'] ?? null;
                if (count($ids) > 100) {
                    $this->fail('The stream kept yielding after a runtime error');
                }
            }
            $this->fail('A runtime error during the stream did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        } finally {
            restore_error_handler();
        }

        $this->assertSame(range(1, 29), array_map('intval', $ids));
        $this->dropStreamFixture($db);
    }

    public function testQueryAfterHalfReadSelectRuns()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->query('SELECT * FROM stream_items');
        $db->fetch();
        $db->query('DROP TABLE stream_items');

        $this->assertFalse($db->hasTable('stream_items'));
        $db->disconnect();
    }

    public function testQueryErrorAfterHalfReadSelectThrows()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->query('SELECT * FROM stream_items');
        $db->fetch();

        set_error_handler(fn() => true, E_WARNING);
        try {
            $db->query('INSERT INTO no_such_table VALUES (1)');
            $this->fail('A failed query did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('no such table', $e->getMessage());
        } finally {
            restore_error_handler();
        }

        $this->dropStreamFixture($db);
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

    public function testSetOptionsMissingDatabaseFileMessage()
    {
        $file = __DIR__ . '/../tmp/bad.sqlite';

        $this->expectException('Pop\Db\Adapter\Exception');
        $this->expectExceptionMessage("Error: The database file '" . $file . "' does not exist.");

        $db = new Sqlite(['database' => $file]);
    }

    public function testConnectMissingDatabaseFileMessage()
    {
        $file = __DIR__ . '/../tmp/gone.sqlite';
        touch($file);

        $db = new Sqlite(['database' => $file]);
        unlink($file);

        $this->expectException('Pop\Db\Adapter\Exception');
        $this->expectExceptionMessage("Error: The database file '" . $file . "' does not exist.");

        $db->connect();
    }

    public function testSqliteConnect()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $db);
        $this->assertStringContainsString('SQLite', $db->getVersion());
    }

    public function testCreateTable()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $profiler = $db->listen(new CallableObject(TestQueryListener::class), ['name' => 'query-listener']);
        $this->assertInstanceOf('Pop\Utils\CallableObject', $db->getListener());

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
    }

    public function testBindParams()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $sql      = $db->createSql();
        $profiler = $db->listen(TestQueryListener::class);

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

        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(1, $db->getNumberOfRows());
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
        $this->assertEquals(1, $db->getNumberOfAffectedRows());

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

        $profiler = $db->listen(TestQueryListener::class);

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $db->prepare($sql)
            ->bindParam(':username', 'testuser3')->execute();

        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testBindValue()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $profiler = $db->listen(TestQueryListener::class);

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $db->prepare($sql)
            ->bindValue(':username', 'testuser3')->execute();

        $this->assertEquals(1, $db->getNumberOfRows());
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
