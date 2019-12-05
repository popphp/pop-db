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
            ->integer('email_id')
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
        $this->assertContains('`email_id` INT', $sql);
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
        $this->db->disconnect();
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
        $this->db->disconnect();
    }

    public function testCurrent()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->column('id');
        $create->constraint('user_id');
        $this->assertEquals('id', $create->getColumn());
        $this->assertEquals('user_id', $create->getConstraint());
        $this->db->disconnect();
    }

    public function testHasColumn()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->int('id')->addColumnAttribute('AUTO_INCREMENT');
        $this->assertTrue($create->hasColumn('id'));
        $this->db->disconnect();
    }

    public function testDefault()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->defaultIs(0)
            ->primary('id');
        $this->assertContains("`active` INT DEFAULT '0'", (string)$schema);
        $this->db->disconnect();
    }

    public function testDefaultNull1()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->defaultIs(null)
            ->primary('id');
        $this->assertContains("`active` INT DEFAULT NULL", (string)$schema);
        $this->db->disconnect();
    }

    public function testDefaultNull2()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->defaultIs('NULL')
            ->primary('id');
        $this->assertContains("`active` INT DEFAULT NULL", (string)$schema);
        $this->db->disconnect();
    }

    public function testNullable()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->nullable()
            ->primary('id');
        $this->assertContains("`active` INT DEFAULT NULL", (string)$schema);
        $this->db->disconnect();
    }

    public function testNotNullable()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->notNullable()
            ->primary('id');
        $this->assertContains("`active` INT NOT NULL", (string)$schema);
        $this->db->disconnect();
    }

    public function testUnsigned()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->unsigned()
            ->primary('id');
        $this->assertContains("`active` INT UNSIGNED", (string)$schema);
        $this->db->disconnect();
    }

    public function testUnique()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)->unique()
            ->int('active')
            ->primary('id');
        $this->assertContains("CREATE UNIQUE INDEX `index_username` ON `users` (`username`);", (string)$schema);
        $this->db->disconnect();
    }

    public function testPrimary()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->primary()
            ->varchar('username', 255)->unique()
            ->int('active');
        $this->assertContains("PRIMARY KEY (`id`)", (string)$schema);
        $this->db->disconnect();
    }

    public function testForeignKey()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->primary()
            ->int('info_id')
            ->varchar('username', 255)->unique()
            ->int('active')
            ->foreignKey('info_id')->references('user_info')->on('id')->onDelete('CASCADE');
        $this->assertContains("ALTER TABLE `users` ADD CONSTRAINT `fk_info_id` FOREIGN KEY (`info_id`) REFERENCES `user_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;", (string)$schema);

        $this->db->disconnect();
    }

}