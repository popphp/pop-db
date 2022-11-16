<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class AlterMysqlTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
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
        $this->assertStringContainsString('ALTER TABLE `users` CHANGE COLUMN `email` `email_address` VARCHAR(255)', $alter->render());
    }

    public function testAlterWithPrecision()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->modifyColumn('price', 'product_price', 'decimal', 16, 2);
        $this->assertStringContainsString('ALTER TABLE `users` CHANGE COLUMN `price` `product_price` DECIMAL(16, 2)', $alter->render());
    }

    public function testAlterWithIndex()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->index('price', 'price_index');
        $this->assertStringContainsString('CREATE INDEX `price_index` ON `users` (`price`);', $alter->render());
    }

    public function testAlterWithConstraint()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->foreignKey('info_id')->references('user_info')->on('id')->onDelete('CASCADE');
        $this->assertStringContainsString('ALTER TABLE `users` ADD CONSTRAINT `fk_info_id` FOREIGN KEY (`info_id`) REFERENCES `user_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;', $alter->render());
    }

    public function testDropColumn()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropColumn('price');
        $this->assertStringContainsString('DROP COLUMN', $alter->render());
    }

    public function testDropIndex()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropIndex('price_index');
        $this->assertStringContainsString('DROP INDEX', $alter->render());
    }

    public function testDropConstraint()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->dropConstraint('price_index');
        $this->assertStringContainsString('DROP FOREIGN KEY', (string)$alter);

        $schema = $this->db->createSchema();
        $schema->reset();
        $schema->dropIfExists('users');
        $schema->execute();

        $this->db->disconnect();
    }

}