<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SchemaStructureMysqlTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host' => '127.0.0.1'
        ]);
    }

    public function testEngineAndCharset()
    {
        $schema = $this->db->createSchema();

        $create = $schema->create('users');
        $create->setEngine('MyISAM');
        $create->setCharset('iso-8859-1');
        $this->assertEquals('MyISAM', $create->getEngine());
        $this->assertEquals('iso-8859-1', $create->getCharset());
        $this->db->disconnect();
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
        $this->assertStringContainsString('`email_id` INT', $sql);
        $this->assertStringContainsString('`id` BIGINT', $sql);
        $this->assertStringContainsString('`info_id` MEDIUMINT', $sql);
        $this->assertStringContainsString('`active` SMALLINT', $sql);
        $this->assertStringContainsString('`verified` TINYINT', $sql);
        $this->assertStringContainsString('`worked` FLOAT', $sql);
        $this->assertStringContainsString('`time_off` REAL', $sql);
        $this->assertStringContainsString('`hourly` DOUBLE', $sql);
        $this->assertStringContainsString('`overtime` DECIMAL', $sql);
        $this->assertStringContainsString('`years` NUMERIC', $sql);
        $this->assertStringContainsString('`birth_date` DATE', $sql);
        $this->assertStringContainsString('`last_click` TIME', $sql);
        $this->assertStringContainsString('`hired` DATETIME', $sql);
        $this->assertStringContainsString('`fired` TIMESTAMP', $sql);
        $this->assertStringContainsString('`started` YEAR', $sql);
        $this->assertStringContainsString('`notes` TEXT', $sql);
        $this->assertStringContainsString('`remarks` TINYTEXT', $sql);
        $this->assertStringContainsString('`comments` MEDIUMTEXT', $sql);
        $this->assertStringContainsString('`history` LONGTEXT', $sql);
        $this->assertStringContainsString('`foo` BLOB', $sql);
        $this->assertStringContainsString('`bar` MEDIUMBLOB', $sql);
        $this->assertStringContainsString('`baz` LONGBLOB', $sql);
        $this->assertStringContainsString('`gender` CHAR', $sql);
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

        $this->assertStringContainsString('`id` INT', $sql);
        $this->assertStringContainsString('`info_id` INT', $sql);
        $this->assertStringContainsString('`email_id` BIGINT', $sql);
        $this->assertStringContainsString('`session_id` SMALLINT', $sql);
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
        $this->assertStringContainsString("`active` INT DEFAULT '0'", (string)$schema);
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
        $this->assertStringContainsString("`active` INT DEFAULT NULL", (string)$schema);
        $this->db->disconnect();
    }

    public function testDefaultNull2()
    {
        $schema = $this->db->createSchema();
        $create = $schema->create('users');
        $create->int('id', 16)
            ->varchar('username', 255)
            ->int('active')->defaultIs('NULL')
            ->primary('id');
        $this->assertStringContainsString("`active` INT DEFAULT NULL", (string)$create);
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
        $this->assertStringContainsString("`active` INT DEFAULT NULL", (string)$schema);
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
        $this->assertStringContainsString("`active` INT NOT NULL", (string)$schema);
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
        $this->assertStringContainsString("`active` INT UNSIGNED", (string)$schema);
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
        $this->assertStringContainsString("CREATE UNIQUE INDEX `index_username` ON `users` (`username`);", (string)$schema);
        $this->db->disconnect();
    }

    public function testPrimary()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->primary()
            ->varchar('username', 255)->unique()
            ->int('active');
        $this->assertStringContainsString("PRIMARY KEY (`id`)", (string)$schema);
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
        $this->assertStringContainsString("ALTER TABLE `users` ADD CONSTRAINT `fk_info_id` FOREIGN KEY (`info_id`) REFERENCES `user_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;", (string)$schema);

        $this->db->disconnect();
    }

}