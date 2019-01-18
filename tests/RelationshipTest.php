<?php

namespace Pop\Db\Test;

use Pop\Db\Record\Relationships;
use Pop\Db\Adapter\Mysql;
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
{

    protected $password = '';

    public function testSetup()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);

        TestAsset\Users::setDb($db);
        TestAsset\UserInfo::setDb($db);
        TestAsset\Orders::setDb($db);
        TestAsset\Products::setDb($db);

        $db->query('DROP TABLE IF EXISTS `ph_users`');
        $db->query('DROP TABLE IF EXISTS `ph_user_info`');
        $db->query('DROP TABLE IF EXISTS `ph_orders`');
        $db->query('DROP TABLE IF EXISTS `ph_products`');

        $usersTable = <<<TABLE
CREATE TABLE IF NOT EXISTS `ph_users` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1001
TABLE;

        $userInfoTable = <<<TABLE
CREATE TABLE IF NOT EXISTS `ph_user_info` (
  `user_id` int(16) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
TABLE;

        $ordersTable = <<<TABLE
CREATE TABLE IF NOT EXISTS `ph_orders` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2001
TABLE;

        $productsTable = <<<TABLE
CREATE TABLE IF NOT EXISTS `ph_products` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `order_id` int(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3001
TABLE;

        $db->query($usersTable);
        $db->query($userInfoTable);
        $db->query($ordersTable);
        $db->query($productsTable);

        $db->query("INSERT INTO `ph_users` (`username`, `password`) VALUES ('admin', '12test34')");
        $db->query("INSERT INTO `ph_user_info` (`user_id`, `address`, `city`, `state`) VALUES (1001, '123 Main St', 'New Orleans', 'LA')");
        $db->query("INSERT INTO `ph_orders` (`name`, `description`) VALUES ('Some Order', 'Some order description')");
        $db->query("INSERT INTO `ph_products` (`order_id`, `name`, `description`) VALUES (2001, 'Some Product', 'Some product description')");
        $db->query("INSERT INTO `ph_products` (`order_id`, `name`, `description`) VALUES (2001, 'Another Product', 'Some other description')");

        $this->assertTrue($db->hasTable('ph_users'));
        $this->assertTrue($db->hasTable('ph_user_info'));
        $this->assertTrue($db->hasTable('ph_orders'));
        $this->assertTrue($db->hasTable('ph_products'));
    }

    public function testHasOne()
    {
        $user = TestAsset\Users::findById(1001);
        $info = $user->info();
        $this->assertEquals('123 Main St', $info->address);
        $this->assertEquals('New Orleans', $info->city);
        $this->assertEquals('LA', $info->state);
    }

    public function testHasMany()
    {
        $order = TestAsset\Orders::findById(2001);
        $products = $order->products();
        $this->assertEquals(2, $products->count());
    }

    public function testBelongsTo()
    {
        $product = TestAsset\Products::findById(3001);
        $order   = $product->parentOrder();
        $this->assertEquals(2001, $order->id);
    }

    public function testHasManyEager()
    {
        $orders = TestAsset\Orders::with('products')->getAll();
        foreach ($orders as $order) {
            $this->assertGreaterThan(0, count($order->products));
        }
    }

    public function testGetForeignTableAndKey()
    {
        $order = TestAsset\Orders::findById(2001);
        $relationship = new Relationships\HasOne($order, 'Products', 'product_id');
        $this->assertEquals('Products', $relationship->getForeignTable());
        $this->assertEquals('product_id', $relationship->getForeignKey());
    }

    public function testRelationshipException()
    {
        $order = TestAsset\Orders::findById(2001);
        $relationship = new Relationships\HasOne($order, null, null);

        $this->expectException('Pop\Db\Record\Relationships\Exception');
        $relationship->getEagerRelationships([1]);
    }

    public function testGetChild()
    {
        $product = TestAsset\Products::findById(3001);
        $relationship = new Relationships\BelongsTo($product, 'Orders', 'order_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Products', $relationship->getChild());
    }

    public function testHasOneGetParent()
    {
        $order = TestAsset\Orders::findById(2001);
        $relationship = new Relationships\HasOne($order, 'Products', 'product_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Orders', $relationship->getParent());
    }

    public function testHasManyGetParent()
    {
        $order = TestAsset\Orders::findById(2001);
        $relationship = new Relationships\HasMany($order, 'Products', 'product_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Orders', $relationship->getParent());
    }

}