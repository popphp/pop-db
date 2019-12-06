<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class AlterPgsqlTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../../tmp/.pgsql')),
            'host' => 'localhost'
        ]);

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('email', 255)
            ->numeric('price', '16,1');

        $schema->execute();
    }

    public function testModify()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->modifyColumn('email', 'email', 'varchar', 255);
        $this->assertContains('ALTER TABLE "users" ALTER COLUMN "email" VARCHAR(255);', $alter->render());
    }

    public function testRename()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users');
        $alter->modifyColumn('email', 'email_address');
        $this->assertContains('ALTER TABLE "users" RENAME COLUMN "email" "email_address";', $alter->render());
    }

    public function testDropIndex()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->dropIndex('product_price');
        $this->assertContains('DROP INDEX "users"."product_price";', $alter->render());
    }

    public function testDropConstraint()
    {
        $schema = $this->db->createSchema();
        $alter = $schema->alter('users')->dropConstraint('product_price');
        $this->assertContains('ALTER TABLE "users" DROP CONSTRAINT "product_price";', $alter->render());
    }

}