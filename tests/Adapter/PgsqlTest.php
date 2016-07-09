<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Sql;
use Pop\Db\Adapter\Pgsql;
use Pop\Db\Adapter\Pdo;

class PgsqlTest extends \PHPUnit_Framework_TestCase
{

    protected $password = '';

    public function testConstructorException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'username' => 'postgres',
            'password' => $this->password
        ]);
    }

    public function testConstructor()
    {
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);

        $db->query('DROP TABLE IF EXISTS ph_users CASCADE');
        $db->query('CREATE SEQUENCE user_id_seq START 1001');

        $table = <<<TABLE
CREATE TABLE IF NOT EXISTS "ph_users" (
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
        $db->query('ALTER SEQUENCE user_id_seq OWNED BY "ph_users"."id";');
        $this->assertInstanceOf('Pop\Db\Adapter\Pgsql', $db);
        $this->assertContains('PostgreSQL', $db->version());
    }

    public function testExecuteException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $db->showError();
    }

    public function testIsInstalled()
    {
        $this->assertTrue(Pgsql::isInstalled());
    }

    public function testLoadTables()
    {
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testLoadTablesFromPdo()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password,
            'type'     => 'pgsql'
        ]);

        $sql = new Sql($db, 'ph_users');
        $this->assertEquals(Sql::PGSQL, $sql->getDbType());
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);

        $sql = new Sql($db, 'ph_users');
        $this->assertEquals(Sql::PGSQL, $sql->getDbType());

        $db->prepare('INSERT INTO ph_users ("username", "password", "email") VALUES ($1, $2, $3)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $this->assertTrue($db->hasResult());
        $this->assertNotNull($db->getResult());
        $this->assertNotNull($db->lastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->numberOfRows());
        $this->assertEquals(0, $db->numberOfFields());
    }

    public function testFetch()
    {
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $db->query('SELECT * FROM ph_users');

        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->numberOfRows());
        $this->assertEquals(7, $db->numberOfFields());
    }

    public function testFetchResults()
    {
        $db = new Pgsql([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => $this->password
        ]);
        $db->prepare('SELECT * FROM "ph_users" WHERE id != $1')
           ->bindParams([0])
           ->execute();

        $rows = $db->fetchResult();
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->numberOfRows());
        $this->assertEquals(7, $db->numberOfFields());

        $db->disconnect();
    }

}
