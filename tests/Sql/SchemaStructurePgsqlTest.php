<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructurePgsqlTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql')),
            'host'     => 'localhost'
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

        $this->assertContains('"id" BIGINT', $sql);
        $this->assertContains('"info_id" INT', $sql);
        $this->assertContains('"active" SMALLINT', $sql);
        $this->assertContains('"verified" INT', $sql);
        $this->assertContains('"worked" FLOAT', $sql);
        $this->assertContains('"time_off" REAL', $sql);
        $this->assertContains('"hourly" DOUBLE', $sql);
        $this->assertContains('"overtime" DECIMAL', $sql);
        $this->assertContains('"years" NUMERIC', $sql);
        $this->assertContains('"birth_date" DATE', $sql);
        $this->assertContains('"last_click" TIME', $sql);
        $this->assertContains('"hired" TIMESTAMP', $sql);
        $this->assertContains('"fired" TIMESTAMP', $sql);
        $this->assertContains('"started" YEAR', $sql);
        $this->assertContains('"notes" TEXT', $sql);
        $this->assertContains('"remarks" TEXT', $sql);
        $this->assertContains('"comments" TEXT', $sql);
        $this->assertContains('"history" TEXT', $sql);
        $this->assertContains('"foo" TEXT', $sql);
        $this->assertContains('"bar" TEXT', $sql);
        $this->assertContains('"baz" TEXT', $sql);
        $this->assertContains('"gender" CHAR', $sql);
    }

    public function testAlternateTypes()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->addColumn('id', 'VARBINARY');

        $sql = (string)$schema;

        $this->assertContains('"id" BYTEA', $sql);
    }

}