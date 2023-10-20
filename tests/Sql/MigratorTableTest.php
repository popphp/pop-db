<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTableTest extends TestCase
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
        \Pop\Db\Test\TestAsset\Migrations::setDb($this->db);
    }

    public function testConstructor()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations2');
        $this->assertInstanceOf('Pop\Db\Sql\Migrator', $migrator);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->getDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->db());
        $this->assertEquals(__DIR__ . '/../tmp/migrations2', $migrator->getPath());
        $this->assertNull($migrator->getCurrent());
        $this->assertTrue($migrator->hasTable());
        $this->assertEquals('Pop\Db\Test\TestAsset\Migrations', $migrator->getTable());
        $this->db->disconnect();
    }

    public function testRun()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations2');
        $this->assertFalse($this->db->hasTable('test_users'));
        $migrator->runAll();
        $this->assertTrue($this->db->hasTable('test_users'));
        $this->assertCount(1, $migrator->getByBatch('batch-1'));
        $this->db->disconnect();
    }

    public function testRollback()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations2');
        $this->assertTrue($this->db->hasTable('test_users'));
        $migrator->rollbackAll();
        $this->assertFalse($this->db->hasTable('test_users'));
        $this->db->disconnect();
    }

}