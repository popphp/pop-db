<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => '127.0.0.1'
        ]);
    }

    public function testConstructor()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations');
        $this->assertInstanceOf('Pop\Db\Sql\Migrator', $migrator);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->getDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->db());
        $this->assertEquals(__DIR__ . '/../tmp/migrations', $migrator->getPath());
        $this->assertNull($migrator->getCurrent());
        $this->db->disconnect();
    }

    public function testSetPathException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/badpath');
        $this->db->disconnect();
    }

    public function testCreate()
    {
        $file = Migrator::create('MyAppMigration', __DIR__ . '/../tmp/migrations');
        $this->assertStringContainsString('my_app_migration', $file);
        $this->assertFileExists($file);
        unlink($file);
    }

    public function testCreateException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $file = Migrator::create('MyAppMigration', __DIR__ . '/../tmp/badpath');
    }

    public function testRun()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations');
        $this->assertFalse($this->db->hasTable('test_users'));
        $migrator->runAll();
        $this->assertTrue($this->db->hasTable('test_users'));
        $this->db->disconnect();
    }

    public function testRollback()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations');
        $this->assertTrue($this->db->hasTable('test_users'));
        $migrator->rollbackAll();
        $this->assertFalse($this->db->hasTable('test_users'));
        $this->db->disconnect();
    }

}