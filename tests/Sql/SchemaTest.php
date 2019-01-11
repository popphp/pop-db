<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Sql;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{

    public function testConstructor()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $this->assertInstanceOf('Pop\Db\Sql\Schema', $schema);
    }

    public function testCreate()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255);

        $this->assertContains('CREATE TABLE "users"', $schema->render());
    }

    public function testDrop()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->disableForeignKeyCheck();
        $schema->drop('users');
        $this->assertContains('DROP TABLE "users"', $schema->render());
        $schema->enableForeignKeyCheck();
    }

    public function testDropAndCreate()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->dropIfExists('users');
        $schema->createIfNotExists('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->integer('test0')
            ->bigInt('test1')
            ->mediumInt('test2')
            ->smallInt('test3')
            ->tinyInt('test4')
            ->float('test5')
            ->real('test6')
            ->double('test7')
            ->decimal('test8')
            ->numeric('test9')
            ->date('test10')
            ->time('test11')
            ->datetime('test12')
            ->timestamp('test13')
            ->year('test14')
            ->text('test15')
            ->tinyText('test16')
            ->mediumText('test17')
            ->longText('test18')
            ->blob('test19')
            ->mediumBlob('test20')
            ->longBlob('test21')
            ->char('test22');

        $this->assertContains('CREATE TABLE IF NOT EXISTS "users"', (string)$schema->render());
    }

    public function testAlter()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->alter('users')->addColumn('email', 'varchar', 255);
        $this->assertContains('ALTER TABLE "users"', $schema->render());
    }

    public function testRename()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->rename('users')->to('app_users');
        $this->assertContains('ALTER TABLE "users"', $schema->render());
    }

    public function testTruncate()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $schema->truncate('users');
        $this->assertContains('TRUNCATE TABLE "users"', $schema->render());
    }

    public function testCreateWithPrimaryKey()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $table = $schema->create('users');
        $table->int('id', 16)->primary()->increment(1);
        $table->int('info_id', 16)->defaultIs(null)->nullable()->unsigned()->unique();
        $table->varchar('username', 255)->notNullable();
        $this->assertTrue($table->hasColumn('username'));
        $this->assertContains('PRIMARY KEY', $table->render());
    }

    public function testCreateWithForeignKeys()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/../tmp/db.sqlite']);
        $schema = $db->createSchema();
        $table = $schema->create('users');
        $table->int('id', 16);
        $table->int('info_id', 16);
        $table->varchar('username', 255);
        $table->foreignKey('info_id')->references('user_info')->on('id')->onDelete(null);
        $this->assertContains(
            'ALTER TABLE "users" ADD CONSTRAINT "fk_info_id" FOREIGN KEY ("info_id") REFERENCES "user_info" ("id") ON DELETE SET NULL ON UPDATE CASCADE;',
            $table->render()
        );
    }

}