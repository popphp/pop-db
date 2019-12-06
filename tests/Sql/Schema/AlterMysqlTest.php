<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class AlterMysqlTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../../tmp/.mysql')),
            'host' => 'localhost'
        ]);
        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('email', 255)
            ->double('price', '16,1');

        $schema->execute();
    }

    public function testModify()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->modifyColumn('email', 'email_address', 'varchar', 255);
        $this->assertContains('ALTER TABLE `users` CHANGE COLUMN `email` `email_address` VARCHAR(255) DEFAULT NULL;', $alter->render());
    }

    public function testAlterWithPrecision()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->modifyColumn('price', 'product_price', 'decimal', 16, 2);
        $this->assertContains('ALTER TABLE `users` CHANGE COLUMN `price` `product_price` DECIMAL(16, 2) DEFAULT NULL;', $alter->render());
    }

    public function testAlterWithIndex()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->index('price', 'price_index');
        $this->assertContains('CREATE INDEX `price_index` ON `users` (`price`);', $alter->render());
    }

    public function testAlterWithConstraint()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->foreignKey('info_id')->references('user_info')->on('id')->onDelete('CASCADE');
        $this->assertContains('ALTER TABLE `users` ADD CONSTRAINT `fk_info_id` FOREIGN KEY (`info_id`) REFERENCES `user_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;', $alter->render());
    }

    public function testDropColumn()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropColumn('price');
        $this->assertContains('DROP COLUMN', $alter->render());
    }

    public function testDropIndex()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropIndex('price_index');
        $this->assertContains('DROP INDEX', $alter->render());
    }

    public function testDropConstraint()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropConstraint('price_index');
        $this->assertContains('DROP FOREIGN KEY', (string)$alter);

        $schema = $this->db->createSchema();
        $schema->reset();
        $schema->dropIfExists('users');
        $schema->execute();

        $this->db->disconnect();
    }

}