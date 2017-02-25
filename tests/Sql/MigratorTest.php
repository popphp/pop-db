<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Migrator;

class MigratorTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $migrator = new Migrator($db, __DIR__ . '/../tmp/migrations');
        $this->assertInstanceOf('Pop\Db\Sql\Migrator', $migrator);
        $this->assertEquals(__DIR__ . '/../tmp/migrations', $migrator->getPath());
        $this->assertNull($migrator->getCurrent());
    }

    public function testCreateException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        Migrator::create('MyNewMigration', __DIR__ . '/../tmp/baddir');
    }

    public function testCreate()
    {
        Migrator::create('MyNewMigration', __DIR__ . '/../tmp/migrations');
        $files         = scandir(__DIR__ . '/../tmp/migrations');
        $migrationFile = null;
        foreach ($files as $file) {
            if (strpos($file, '_my_new_migration.php') !== false) {
                $migrationFile = $file;
                break;
            }
        }
        $this->assertFileExists(__DIR__ . '/../tmp/migrations/' . $migrationFile);

        if (file_exists(__DIR__ . '/../tmp/migrations/' . $migrationFile)) {
            unlink(__DIR__ . '/../tmp/migrations/' . $migrationFile);
        }
    }

    public function testRun()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $migrator = new Migrator($db, __DIR__ . '/../tmp/migrations');
        $migrator->run();
        $this->assertContains('test_users', $db->getTables());
    }

    public function testRollback()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__ . '/../tmp/db.sqlite']);
        $migrator = new Migrator($db, __DIR__ . '/../tmp/migrations');
        $migrator->rollback();
        $this->assertNotContains('test_users', $db->getTables());
        if (file_exists(__DIR__ . '/../tmp/migrations/.current')) {
            unlink(__DIR__ . '/../tmp/migrations/.current');
        }
    }

}