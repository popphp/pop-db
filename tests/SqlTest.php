<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Sql;

class SqlTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $db   = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $db2  = Db::connect('pdo', ['database' => __DIR__  . '/tmp/db.sqlite', 'type' => 'sqlite']);
        $sql  = $db->createSql();
        $sql2 = $db2->createSql();
        $this->assertInstanceOf('Pop\Db\Sql', $sql);
        $this->assertInstanceOf('Pop\Db\Sql', $sql2);
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $sql->db());
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', $sql->getDb());
    }

    public function testDbTypes()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $sql = $db->createSql();
        $this->assertTrue($sql->isSqlite());
        $this->assertFalse($sql->isMysql());
        $this->assertFalse($sql->isPgsql());
        $this->assertFalse($sql->isSqlsrv());
    }

    public function testSetAndGetIdQuoteType()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->setIdQuoteType(Sql::DOUBLE_QUOTE);
        $this->assertEquals(Sql::DOUBLE_QUOTE, $sql->getIdQuoteType());
        $this->assertEquals("'hello'", $sql->quote('hello'));
        $this->assertEquals('"users"."username"', $sql->quoteId('users.username'));
    }

    public function testSetAndGetPlaceholder()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->setPlaceholder(':');
        $this->assertEquals(':', $sql->getPlaceholder());
    }

    public function testVerbs()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->reset();
        $this->assertInstanceOf('Pop\Db\Sql\Select', $sql->select([0 => 'username', 'foo' => 'bar']));
        $this->assertInstanceOf('Pop\Db\Sql\Insert', $sql->insert('users'));
        $this->assertInstanceOf('Pop\Db\Sql\Update', $sql->update('users'));
        $this->assertInstanceOf('Pop\Db\Sql\Delete', $sql->delete('users'));
    }

    public function testRender()
    {
        $db  = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $sql = $db->createSql();
        $sql->select()->from('users', 'u');
        $this->assertEquals('(SELECT * FROM "users") AS "u"', $sql->render());
        $sql->insert('users')->values(['username' => 'admin']);
        $this->assertEquals('INSERT INTO "users" ("username") VALUES (\'admin\')', $sql->render());
        $sql->update('users')->values(['username' => 'admin2'])->where('id = 1');
        $this->assertEquals('UPDATE "users" SET "username" = \'admin2\' WHERE ("id" = 1)', $sql->render());
        $sql->delete('users')->where('id = 1');
        $this->assertEquals('DELETE FROM "users" WHERE ("id" = 1)', (string)$sql);
        if (file_exists(__DIR__  . '/tmp/db.sqlite')) {
            unlink(__DIR__  . '/tmp/db.sqlite');
        }
    }

}