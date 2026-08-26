<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructureSqliteTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);

        $this->db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);
    }

    public function testTypes()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->bigInt('id')
            ->mediumInt('info_id')
            ->smallInt('active')
            ->tinyInt('verified')
            ->float('worked')
            ->real('time_off')
            ->double('hourly')
            ->decimal('overtime')
            ->numeric('years')
            ->date('birth_date')
            ->time('last_click')
            ->datetime('hired')
            ->timestamp('fired')
            ->year('started')
            ->text('notes')
            ->tinyText('remarks')
            ->mediumText('comments')
            ->longText('history')
            ->blob('foo')
            ->mediumBlob('bar')
            ->longBlob('baz')
            ->char('gender');

        $sql = (string)$schema;

        $this->assertStringContainsString('"id" INTEGER', $sql);
        $this->assertStringContainsString('"info_id" INTEGER', $sql);
        $this->assertStringContainsString('"active" INTEGER', $sql);
        $this->assertStringContainsString('"verified" INTEGER', $sql);
        $this->assertStringContainsString('"worked" REAL', $sql);
        $this->assertStringContainsString('"time_off" REAL', $sql);
        $this->assertStringContainsString('"hourly" REAL', $sql);
        $this->assertStringContainsString('"overtime" NUMERIC', $sql);
        $this->assertStringContainsString('"years" NUMERIC', $sql);
        $this->assertStringContainsString('"birth_date" DATE', $sql);
        $this->assertStringContainsString('"last_click" TIME', $sql);
        $this->assertStringContainsString('"hired" DATETIME', $sql);
        $this->assertStringContainsString('"fired" DATETIME', $sql);
        $this->assertStringContainsString('"started" YEAR', $sql);
        $this->assertStringContainsString('"notes" TEXT', $sql);
        $this->assertStringContainsString('"remarks" TEXT', $sql);
        $this->assertStringContainsString('"comments" TEXT', $sql);
        $this->assertStringContainsString('"history" TEXT', $sql);
        $this->assertStringContainsString('"foo" BLOB', $sql);
        $this->assertStringContainsString('"bar" BLOB', $sql);
        $this->assertStringContainsString('"baz" BLOB', $sql);
        $this->assertStringContainsString('"gender" CHAR', $sql);
    }

    public function testAlternateTypes()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->addColumn('info_id', 'SERIAL');
        $create->addColumn('email_id', 'BIGSERIAL');
        $create->addColumn('session_id', 'SMALLSERIAL');

        $sql = (string)$schema;

        $this->assertStringContainsString('"info_id" INTEGER', $sql);
        $this->assertStringContainsString('"email_id" INTEGER', $sql);
        $this->assertStringContainsString('"session_id" INTEGER', $sql);

        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

    public function testForeignKeyConstraintIsInlinedOnCreate()
    {
        $schema = $this->db->createSchema();
        $schema->create('widgets')
            ->int('id', 16)->increment()
            ->varchar('name', 255)
            ->primary('id');
        $schema->execute();

        $schema2 = $this->db->createSchema();
        $schema2->create('widget_notes')
            ->int('id', 16)->increment()
            ->int('widget_id', 16)
            ->text('note')
            ->primary('id')
            ->foreignKey('widget_id', 'fk_widget_id')->references('widgets')->on('id')->onDelete('CASCADE');

        // SQLite doesn't support ALTER TABLE ADD CONSTRAINT, so the FOREIGN KEY has to be
        // declared inline in the CREATE TABLE statement rather than appended as a separate
        // ALTER TABLE statement
        $sql = (string)$schema2;
        $this->assertStringContainsString(
            'CONSTRAINT "fk_widget_id" FOREIGN KEY ("widget_id") REFERENCES "widgets" ("id")', $sql
        );
        $this->assertStringNotContainsString('ALTER TABLE', $sql);

        $schema2->execute();

        $this->assertTrue(in_array('widget_notes', $this->db->getTables()));

        $this->db->disconnect();
        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

    public function testForeignKeyConstraintOnAlterThrowsException()
    {
        $schema = $this->db->createSchema();
        $schema->create('gadgets')
            ->int('id', 16)->increment()
            ->primary('id');
        $schema->execute();

        $schema2 = $this->db->createSchema();
        $schema2->create('gadget_notes')
            ->int('id', 16)->increment()
            ->int('gadget_id', 16)
            ->primary('id');
        $schema2->execute();

        // SQLite can't add a FOREIGN KEY constraint to an existing table via ALTER TABLE -
        // that requires recreating the table, so this must fail clearly instead of sending
        // invalid SQL to the driver
        $this->expectException('Pop\Db\Sql\Schema\Exception');

        $schema3 = $this->db->createSchema();
        $schema3->alter('gadget_notes')
            ->foreignKey('gadget_id', 'fk_gadget_id')->references('gadgets')->on('id')->onDelete('CASCADE');
        $schema3->execute();
    }

}