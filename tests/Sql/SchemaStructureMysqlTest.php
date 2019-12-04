<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructureMysqlTest extends TestCase
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
        $this->assertContains('`id` BIGINT', $sql);
        $this->assertContains('`info_id` MEDIUMINT', $sql);
        $this->assertContains('`active` SMALLINT', $sql);
        $this->assertContains('`verified` TINYINT', $sql);
        $this->assertContains('`worked` FLOAT', $sql);
        $this->assertContains('`time_off` REAL', $sql);
        $this->assertContains('`hourly` DOUBLE', $sql);
        $this->assertContains('`overtime` DECIMAL', $sql);
        $this->assertContains('`years` NUMERIC', $sql);
        $this->assertContains('`birth_date` DATE', $sql);
        $this->assertContains('`last_click` TIME', $sql);
        $this->assertContains('`hired` DATETIME', $sql);
        $this->assertContains('`fired` TIMESTAMP', $sql);
        $this->assertContains('`started` YEAR', $sql);
        $this->assertContains('`notes` TEXT', $sql);
        $this->assertContains('`remarks` TINYTEXT', $sql);
        $this->assertContains('`comments` MEDIUMTEXT', $sql);
        $this->assertContains('`history` LONGTEXT', $sql);
        $this->assertContains('`foo` BLOB', $sql);
        $this->assertContains('`bar` MEDIUMBLOB', $sql);
        $this->assertContains('`baz` LONGBLOB', $sql);
        $this->assertContains('`gender` CHAR', $sql);
    }

    public function testAlternateTypes()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->addColumn('id', 'INTEGER');
        $create->addColumn('info_id', 'SERIAL');
        $create->addColumn('email_id', 'BIGSERIAL');
        $create->addColumn('session_id', 'SMALLSERIAL');

        $sql = (string)$schema;

        $this->assertContains('`id` INT', $sql);
        $this->assertContains('`info_id` INT', $sql);
        $this->assertContains('`email_id` BIGINT', $sql);
        $this->assertContains('`session_id` SMALLINT', $sql);
    }

}