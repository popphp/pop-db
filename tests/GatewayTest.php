<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Sql;
use Pop\Db\Gateway;

class GatewayTest extends \PHPUnit_Framework_TestCase
{

    public function testRowGetSql()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $row = new Gateway\Row($sql, 'id', 'ph_users');
        $this->assertInstanceOf('Pop\Db\Sql', $row->getSql());
        $this->assertInstanceOf('Pop\Db\Sql', $row->sql());
        $this->assertEquals('ph_users', $row->getTable());
        $this->assertEquals(1, count($row->getPrimaryKeys()));
    }

    public function testRowFindNoKeysException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $row = new Gateway\Row($sql);
        $row->find(['id' => 1001]);
    }

    public function testRowFindMismatchException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $row = new Gateway\Row($sql, 'id', 'ph_users');
        $row->find(['id' => 1001, 'username' => 'testuser']);
    }

    public function testRowFindNoTableException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $row = new Gateway\Row($sql, 'id');
        $row->find(['id' => 1001]);
    }

    public function testRowDeleteNoKeysException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $row = new Gateway\Row($sql);
        $row->delete(['id' => 1001]);
    }

    public function testRowDeleteMismatchException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $row = new Gateway\Row($sql, 'id', 'ph_users');
        $row->delete(['id' => 1001, 'username' => 'testuser']);
    }

    public function testRowDeleteNoTableException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $row = new Gateway\Row($sql, 'id');
        $row->delete(['id' => 1001]);
    }

    public function testRowMagicMethods()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $row = new Gateway\Row($sql, 'id', 'ph_users');
        $row->username = 'testuser';
        $this->assertEquals('testuser', $row->username);
        $this->assertTrue(isset($row->username));
        unset($row->username);
        $this->assertFalse(isset($row->username));
    }

    public function testTableGetNumberOfRows()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $table = new Gateway\Table($sql, 'ph_users');
        $this->assertInstanceOf('Pop\Db\Sql', $table->getSql());
        $this->assertEquals(0, $table->getNumberOfRows());
        $this->assertEquals(0, count($table->getRows()));
    }

    public function testTableSelectNoTableException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $table = new Gateway\Table($sql);
        $table->select();
    }

    public function testTableSelectOptions()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $table = new Gateway\Table($sql, 'ph_users');
        $table->select(null, null, null, [
            'limit'  => 1,
            'offset' => 0,
            'order'  => 'id ASC'
        ]);

        $this->assertEquals(0, $table->getNumberOfRows());
    }

    public function testTableInsert()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $table = new Gateway\Table($sql, 'ph_users');
        $table->insert([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ]);

        $this->assertEquals(0, $table->getNumberOfRows());
    }

    public function testTableInsertNoTableException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $table = new Gateway\Table($sql);
        $table->insert([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ]);
    }

    public function testTableUpdate()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db, 'ph_users');

        $table = new Gateway\Table($sql, 'ph_users');
        $table->update([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ], 'id = :id', ['id' => 1001]);

        $this->assertEquals(0, $table->getNumberOfRows());
    }

    public function testTableUpdateNoTableException()
    {
        $this->setExpectedException('Pop\Db\Gateway\Exception');
        $db  = Db::connect('sqlite', ['database' => __DIR__ . '/tmp/db.sqlite']);
        $sql = new Sql($db);

        $table = new Gateway\Table($sql);
        $table->update([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ]);
    }


}
