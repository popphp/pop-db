<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Pdo;
use PHPUnit\Framework\TestCase;

class PdoPgsqlTest extends TestCase
{

    protected $password = '';

    public function setUp()
    {
        $this->password = trim(file_get_contents(__DIR__ . '/../tmp/.pgsql'));
    }

    public function testConstructorException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Pdo([
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);
    }

    public function testPgsqlConnect()
    {
        $db = Db::pdoConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
    }

    public function testConstructor()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $db->query('DROP TABLE IF EXISTS users CASCADE');
        $db->query('CREATE SEQUENCE user_id_seq START 1001');

        $table = <<<TABLE
CREATE TABLE IF NOT EXISTS "users" (
  "id" integer NOT NULL DEFAULT nextval('user_id_seq'),
  "role_id" integer,
  "username" varchar(255) NOT NULL,
  "password" varchar(255) NOT NULL,
  "email" varchar(255) NOT NULL,
  "active" integer,
  "verified" integer,
  PRIMARY KEY ("id")
)
TABLE;
        $db->query($table);
        $db->query('ALTER SEQUENCE user_id_seq OWNED BY "users"."id";');

        $debugResults = $profiler->prepareAsString();
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
        $this->assertContains('PDO pgsql', $db->getVersion());
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
        $this->assertContains('CREATE TABLE IF NOT EXISTS "users"', $debugResults);
    }

    public function testGetTables()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);
        $this->assertContains('users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);

        $profiler = $db->listen('Pop\Debug\Handler\QueryHandler');

        $db->beginTransaction();

        $db->prepare('INSERT INTO users ("username", "password", "email") VALUES (:username, :password, :email)')
           ->bindParams([
               'username' => 'testuser',
               'password' => '12test34',
               'email'    => 'test@test.com'
           ])
           ->execute();

        $db->commit();

        $debugResults = $profiler->prepareAsString();

        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(1, $db->getNumberOfRows());
        $this->assertContains('Start:', $debugResults);
        $this->assertContains('Finish:', $debugResults);
        $this->assertContains('Elapsed:', $debugResults);
        $this->assertContains('INSERT INTO users', $debugResults);
    }

    public function testRollback()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);

        $db->beginTransaction();

        $db->prepare('INSERT INTO users ("username", "password", "email") VALUES (:username, :password, :email)')
            ->bindParams([
                'username' => 'testuser',
                'password' => '12test34',
                'email'    => 'test@test.com'
            ])
            ->execute();

        $db->rollback();
        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testFetch()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
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
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);
        $db->prepare('SELECT * FROM "users" WHERE id != :id')
           ->bindParams(['id' => 0])
           ->execute();

        $rows = $db->fetchAll();
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->getNumberOfRows());
    }

    public function testDropTable()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);

        $schema = $db->createSchema();
        $schema->drop('users');

        $this->assertTrue($db->hasTable('users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('users'));

        $db->disconnect();
    }

}
