<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;
use PHPUnit\Framework\TestCase;
use Pop\Db\Test\TestAsset\TestQueryListener;
use Pop\Db\Test\TestAsset\StreamTestTrait;
use Pop\Db\Adapter\AbstractAdapter;

class PdoSqliteTest extends TestCase
{

    use StreamTestTrait;

    protected function createStreamAdapter(): AbstractAdapter
    {
        touch(__DIR__ . '/../tmp/stream.sqlite');
        return Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/stream.sqlite',
            'type'     => 'sqlite'
        ]);
    }

    protected function streamParamQuery(): array
    {
        return ['SELECT * FROM stream_items WHERE id > :id ORDER BY id', ['id' => 40]];
    }

    public function testFetchAllAfterQuery()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->query('SELECT * FROM stream_items ORDER BY id');
        $rows = $db->fetchAll();

        $this->assertCount(50, $rows);
        $this->assertEquals('item1', $rows[0]['name']);
        $this->dropStreamFixture($db);
    }

    public function testQueryErrorThrowsAdapterException()
    {
        $db = $this->createStreamAdapter();

        try {
            $db->query('SELECT * FROM no_such_table');
            $this->fail('A failed query did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('no such table', $e->getMessage());
        }
        $db->disconnect();
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

    public static function tearDownAfterClass(): void
    {
        // setUp() creates db.sqlite for every test, including the stream tests that run after
        // the test that removes it
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

    public function testSetOptionsMissingDatabaseFileMessage()
    {
        $file = __DIR__ . '/../tmp/bad.sqlite';

        $this->expectException('Pop\Db\Adapter\Exception');
        $this->expectExceptionMessage("Error: The database file '" . $file . "' does not exist.");

        $db = new Pdo([
            'database' => $file,
            'type'     => 'sqlite'
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

        $profiler = $db->listen(TestQueryListener::class);

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
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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

        $debugParams  = $db->debugDumpParams(true);

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertNotNull($db->getNumberOfFields());
        $this->assertNotNull($db->getCountOfFields());
        $this->assertNotNull($db->getCountOfRows());
        $this->assertNotNull($db->fetchColumn(1));
        $this->assertNotNull($db->closeCursor());
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

        $profiler = $db->listen(TestQueryListener::class);

        $sql = $db->createSql();

        $sql->insert()->into('users')->values([
            'username' => ':username'
        ]);

        $username = 'testuser3';
        $db->prepare($sql)
            ->bindParam(':username', $username)->execute();

        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testBindValue()
    {
        $db = Db::pdoConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite',
            'type'     => 'sqlite'
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

    public function testError()
    {
        try {
            $db = Db::pdoConnect([
                'database' => __DIR__ . '/../tmp/db.sqlite',
                'type'     => 'sqlite'
            ]);

            $profiler = $db->listen(TestQueryListener::class);

            $sql = $db->createSql();

            $sql->select()->from('bad_table');
            $db->query($sql);
            $this->fail('A query on a missing table did not throw');
        } catch (\Exception|\Error $e) {
            // query() reports a driver error as the adapter's own exception
            $this->assertEquals('Pop\Db\Adapter\Exception', get_class($e));
            $this->assertStringContainsString('no such table', $e->getMessage());
        }
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
