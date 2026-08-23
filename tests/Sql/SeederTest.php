<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Seeder;
use PHPUnit\Framework\TestCase;

class SeederTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
    }

    public function testCreate()
    {
        $seedFile = Seeder::create('MyFirstSeeder', __DIR__ . '/../tmp/seeds');
        $this->assertFileExists($seedFile);
        unlink($seedFile);
        $this->db->disconnect();
    }

    public function testCreateException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $seedFile = Seeder::create('MyFirstSeeder', __DIR__ . '/bad-path');
    }

    public function testRun1()
    {
        $seedFiles = Seeder::run($this->db, __DIR__ . '/../tmp/seeds');
        $this->assertTrue(in_array('users', $this->db->getTables()));
        $this->assertTrue(is_array($seedFiles));
        $this->assertNotEmpty($seedFiles);

        $this->db->query('DROP TABLE `users`');
        $this->db->disconnect();
    }

    public function testRun2()
    {
        $seedFiles = Seeder::run($this->db, __DIR__ . '/../tmp/seeds2');
        $this->assertTrue(in_array('users', $this->db->getTables()));
        $this->assertTrue(is_array($seedFiles));
        $this->assertNotEmpty($seedFiles);

        $this->db->query('DROP TABLE `users`');
        $this->db->disconnect();
    }

    public function testRunPgsqlClearsExistingTablesWithCascade()
    {
        $db     = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $schema = $db->createSchema();
        $schema->create('leftover_table')->int('id', 16);
        $schema->execute();

        $seedFiles = Seeder::run($db, __DIR__ . '/../tmp/seeds_pgsql');
        $this->assertFalse(in_array('leftover_table', $db->getTables()));
        $this->assertTrue(in_array('users', $db->getTables()));

        $db->query('DROP TABLE "users"');
        $db->disconnect();
    }

    public function testRunSqliteClearsExistingTables()
    {
        touch(__DIR__ . '/../tmp/seeder.sqlite');
        $db     = Db::sqliteConnect(['database' => __DIR__ . '/../tmp/seeder.sqlite']);
        $schema = $db->createSchema();
        $schema->create('leftover_table')->int('id', 16);
        $schema->execute();

        $seedFiles = Seeder::run($db, __DIR__ . '/../tmp/seeds_sqlite');
        $this->assertFalse(in_array('leftover_table', $db->getTables()));
        $this->assertTrue(in_array('users', $db->getTables()));

        $db->disconnect();
        @unlink(__DIR__ . '/../tmp/seeder.sqlite');
    }

    public function testRunNamespacedSeedFile()
    {
        $seedFiles = Seeder::run($this->db, __DIR__ . '/../tmp/seeds3');
        $this->assertTrue(in_array('namespaced_users', $this->db->getTables()));
        $this->assertNotEmpty($seedFiles);

        $this->db->query('DROP TABLE `namespaced_users`');
        $this->db->disconnect();
    }

    public function testRunException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $seedFiles = Seeder::run($this->db, __DIR__ . '/bad-path');
        $this->db->disconnect();
    }

}