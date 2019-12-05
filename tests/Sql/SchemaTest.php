<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host' => 'localhost'
        ]);
    }

    public function testCreateIfNotExists()
    {
        $schema = $this->db->createSchema();
        $schema->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');
        $this->assertContains('CREATE TABLE IF NOT EXISTS `users`', (string)$schema);
    }

    public function testDropIfExists()
    {
        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $this->assertContains('DROP TABLE IF EXISTS `users`', (string)$schema);
    }

    public function testAlter()
    {
        $schema = $this->db->createSchema();
        $schema->alter('users')->addColumn('email', 'varchar', 255);
        $this->assertContains('ALTER TABLE `users`', (string)$schema);
    }

    public function testRename()
    {
        $schema = $this->db->createSchema();
        $schema->rename('users')->to('users_table');
        $this->assertContains('RENAME TABLE `users` TO `users_table`', (string)$schema);
    }

    public function testTruncate()
    {
        $schema = $this->db->createSchema();
        $schema->truncate('users');
        $this->assertContains('TRUNCATE TABLE `users`', (string)$schema);
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
        $this->assertContains('SET foreign_key_checks = 0', (string)$schema);
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
        $this->assertContains('PRAGMA foreign_keys=off;', (string)$schema);
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