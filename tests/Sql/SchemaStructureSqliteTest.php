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

}