<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructurePgsqlTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql')),
            'host'     => '127.0.0.1'
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

        $this->assertStringContainsString('"id" BIGINT', $sql);
        $this->assertStringContainsString('"info_id" INT', $sql);
        $this->assertStringContainsString('"active" SMALLINT', $sql);
        $this->assertStringContainsString('"verified" SMALLINT', $sql);
        $this->assertStringContainsString('"worked" FLOAT', $sql);
        $this->assertStringContainsString('"time_off" REAL', $sql);
        $this->assertStringContainsString('"hourly" DOUBLE', $sql);
        $this->assertStringContainsString('"overtime" DECIMAL', $sql);
        $this->assertStringContainsString('"years" NUMERIC', $sql);
        $this->assertStringContainsString('"birth_date" DATE', $sql);
        $this->assertStringContainsString('"last_click" TIME', $sql);
        $this->assertStringContainsString('"hired" TIMESTAMP', $sql);
        $this->assertStringContainsString('"fired" TIMESTAMP', $sql);
        $this->assertStringContainsString('"started" YEAR', $sql);
        $this->assertStringContainsString('"notes" TEXT', $sql);
        $this->assertStringContainsString('"remarks" TEXT', $sql);
        $this->assertStringContainsString('"comments" TEXT', $sql);
        $this->assertStringContainsString('"history" TEXT', $sql);
        $this->assertStringContainsString('"foo" TEXT', $sql);
        $this->assertStringContainsString('"bar" TEXT', $sql);
        $this->assertStringContainsString('"baz" TEXT', $sql);
        $this->assertStringContainsString('"gender" CHAR', $sql);
    }

    public function testAlternateTypes()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->addColumn('id', 'VARBINARY');

        $sql = (string)$schema;

        $this->assertStringContainsString('"id" BYTEA', $sql);
    }

}