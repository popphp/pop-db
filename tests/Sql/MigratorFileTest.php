<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorFileTest extends TestCase
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

    public function testConstructor()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations');
        $this->assertInstanceOf('Pop\Db\Sql\Migrator', $migrator);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->getDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $migrator->db());
        $this->assertEquals(__DIR__ . '/../tmp/migrations', $migrator->getPath());
        $this->assertNull($migrator->getCurrent());
        $this->assertFalse($migrator->hasTable());
        $this->assertEquals('', $migrator->getTable());
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

    public function testRollbackIncludesMigrationClassNotYetLoaded()
    {
        $this->assertFalse(class_exists('FreshIncludeTestMigration', false));

        // Create the table by hand (bypassing runAll(), which would include the migration
        // class) so down() has something real to drop, then point '.current' at it directly
        $schema = $this->db->createSchema();
        $schema->create('test_users_fresh')->int('id', 16)->varchar('username', 255)->primary('id');
        $schema->execute();
        file_put_contents(__DIR__ . '/../tmp/migrations4/.current', '20191206142444');

        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations4');
        $this->assertFalse(class_exists('FreshIncludeTestMigration', false));

        $migrator->rollback(1);

        $this->assertTrue(class_exists('FreshIncludeTestMigration', false));
        $this->assertFalse($this->db->hasTable('test_users_fresh'));
        $this->db->disconnect();
    }

    public function testClearCurrentRemovesLeftoverCurrentMarkerFile()
    {
        // rollback() already removes the '.current' marker file as it processes the last
        // step, so clearCurrent()'s own unlink is normally a no-op safety net by the time it
        // runs - exercised directly here against a contrived leftover file to confirm it works
        file_put_contents(__DIR__ . '/../tmp/migrations/.current', '20191206142442');

        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations');
        $method   = new \ReflectionMethod($migrator, 'clearCurrent');
        $method->invoke($migrator);

        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/migrations/.current');
        $this->db->disconnect();
    }

    public function testNamespacedMigrationFileIsParsedAndRunnable()
    {
        $migrator = new Migrator($this->db, __DIR__ . '/../tmp/migrations3');
        $this->assertFalse($this->db->hasTable('test_users_ns'));
        $migrator->runAll();
        $this->assertTrue($this->db->hasTable('test_users_ns'));

        $migrator->rollbackAll();
        $this->assertFalse($this->db->hasTable('test_users_ns'));
        $this->db->disconnect();
    }

}