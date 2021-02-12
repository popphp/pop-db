<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Schema\Formatter;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
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

    public function testCreateIfNotExists()
    {
        $schema = $this->db->createSchema();
        $schema->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `users`', (string)$schema);
        $this->db->disconnect();
    }

    public function testDropIfExists()
    {
        $schema = $this->db->createSchema();
        $drop   = $schema->dropIfExists('users')->cascade();
        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`', (string)$drop);
        $this->db->disconnect();
    }

    public function testAlter()
    {
        $schema = $this->db->createSchema();
        $schema->alter('users')->addColumn('email', 'varchar', 255);
        $this->assertStringContainsString('ALTER TABLE `users`', (string)$schema);
        $this->db->disconnect();
    }

    public function testRename()
    {
        $schema = $this->db->createSchema();
        $rename = $schema->rename('users');
        $rename->to('users_table');
        $this->assertEquals('users_table', $rename->getTo());
        $this->assertStringContainsString('RENAME TABLE `users` TO `users_table`', (string)$rename);
        $this->db->disconnect();
    }

    public function testTruncate()
    {
        $schema   = $this->db->createSchema();
        $truncate = $schema->truncate('users')->cascade();
        $this->assertStringContainsString('TRUNCATE TABLE `users`', (string)$truncate);
        $this->db->disconnect();
    }

    public function testForeignKeyCheck()
    {
        $schema = $this->db->createSchema();
        $schema->enableForeignKeyCheck();
        $schema->disableForeignKeyCheck()
            ->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');
        $this->assertStringContainsString('SET foreign_key_checks = 0', (string)$schema);
        $this->db->disconnect();
    }

    public function testForeignKeyCheckSqlite()
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);

        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $schema = $db->createSchema();
        $schema->enableForeignKeyCheck();
        $schema->disableForeignKeyCheck()
            ->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');
        $this->assertStringContainsString('PRAGMA foreign_keys=off;', (string)$schema);
    }

    public function testCreateExecute()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');
        $schema->execute();
        $this->assertTrue($this->db->hasTable('users'));
        $this->db->disconnect();
    }

    public function testAlterExecute()
    {
        $schema = $this->db->createSchema();
        $alter  = $schema->alter('users')->addColumn('email', 'varchar', 255);
        $info   = $alter->getInfo();

        $this->assertFalse(isset($info['columns']['email']));

        $schema->execute();
        $alter  = $schema->alter('users');
        $info   = $alter->getInfo();
        $this->assertTrue($this->db->hasTable('users'));
        $this->assertEquals('users', $alter->getTable());
        $this->assertTrue(isset($info['columns']['email']));
        $schema->reset();
        $this->db->disconnect();
    }

    public function testTruncateExecute()
    {
        $schema = $this->db->createSchema();
        $schema->truncate('users');
        $schema->execute();
        $this->assertTrue($this->db->hasTable('users'));
    }

    public function testRenameExecute()
    {
        $schema = $this->db->createSchema();
        $schema->rename('users')->to('users_table');
        $schema->execute();
        $this->assertFalse($this->db->hasTable('users'));
        $this->assertTrue($this->db->hasTable('users_table'));
        $this->db->disconnect();
    }

    public function testDropExecute()
    {
        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();
        $schema->drop('users_table');
        $schema->execute();
        $this->assertFalse($this->db->hasTable('users'));

        $this->db->disconnect();
    }

    public function testCreateExecuteSqlite()
    {
        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $schema = $db->createSchema();
        $schema->disableForeignKeyCheck()
            ->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');

        $schema->execute();
        $this->assertTrue($db->hasTable('users'));

        $schema->reset();
        $schema->drop('users');
        $schema->execute();

        $this->assertFalse($db->hasTable('users'));

        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

}