<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructureSqliteTest extends TestCase
{

    protected $db = null;

    public function setUp()
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

        $this->assertContains('"id" INTEGER', $sql);
        $this->assertContains('"info_id" INTEGER', $sql);
        $this->assertContains('"active" INTEGER', $sql);
        $this->assertContains('"verified" INTEGER', $sql);
        $this->assertContains('"worked" REAL', $sql);
        $this->assertContains('"time_off" REAL', $sql);
        $this->assertContains('"hourly" REAL', $sql);
        $this->assertContains('"overtime" NUMERIC', $sql);
        $this->assertContains('"years" NUMERIC', $sql);
        $this->assertContains('"birth_date" DATE', $sql);
        $this->assertContains('"last_click" TIME', $sql);
        $this->assertContains('"hired" DATETIME', $sql);
        $this->assertContains('"fired" DATETIME', $sql);
        $this->assertContains('"started" YEAR', $sql);
        $this->assertContains('"notes" TEXT', $sql);
        $this->assertContains('"remarks" TEXT', $sql);
        $this->assertContains('"comments" TEXT', $sql);
        $this->assertContains('"history" TEXT', $sql);
        $this->assertContains('"foo" BLOB', $sql);
        $this->assertContains('"bar" BLOB', $sql);
        $this->assertContains('"baz" BLOB', $sql);
        $this->assertContains('"gender" CHAR', $sql);
    }

    public function testAlternateTypes()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->addColumn('info_id', 'SERIAL');
        $create->addColumn('email_id', 'BIGSERIAL');
        $create->addColumn('session_id', 'SMALLSERIAL');

        $sql = (string)$schema;

        $this->assertContains('"info_id" INTEGER', $sql);
        $this->assertContains('"email_id" INTEGER', $sql);
        $this->assertContains('"session_id" INTEGER', $sql);

        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

}