<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\OrderItems;
use PHPUnit\Framework\TestCase;

class IncrementTraitTest extends TestCase
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

        $schema->dropIfExists('order_items');
        $schema->execute();

        $schema->create('order_items')
            ->int('id', 16)->increment()
            ->int('order_id', 16)
            ->int('row_id', 16)
            ->primary('id');

        $schema->execute();

        OrderItems::setDb($this->db);
    }

    public function tearDown(): void
    {
        $schema = $this->db->createSchema();
        $schema->dropIfExists('order_items');
        $schema->execute();
        $this->db->disconnect();
    }

    public function testNextReturnsStartWhenNoRowsExist()
    {
        $this->assertEquals(1, OrderItems::next(['order_id' => 100]));
    }

    public function testNextReturnsCustomStartWhenNoRowsExist()
    {
        $this->assertEquals(5, OrderItems::next(['order_id' => 100], 5));
    }

    public function testNextReturnsHighestRowIdPlusOne()
    {
        (new OrderItems(['order_id' => 100, 'row_id' => 1]))->save();
        (new OrderItems(['order_id' => 100, 'row_id' => 2]))->save();
        (new OrderItems(['order_id' => 100, 'row_id' => 3]))->save();

        $this->assertEquals(4, OrderItems::next(['order_id' => 100]));
    }

}
