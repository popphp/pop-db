<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Sql;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
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
    }

    public function testInitSqlConfig()
    {
        $sql = $this->db->createSql();
        $sql->setIdQuoteType(Sql::BACKTICK);
        $sql->setPlaceholder('?');
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $sql->db());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $sql->getDb());
        $this->assertEquals(Sql::BACKTICK, $sql->getIdQuoteType());
        $this->assertEquals('?', $sql->getPlaceholder());
        $this->assertEquals("`", $sql->getOpenQuote());
        $this->assertEquals("`", $sql->getCloseQuote());
        $this->assertEquals('`pop_users`.`id`', $sql->quoteId('pop_users.id'));
        $this->db->disconnect();
    }

    public function testInitSqlConfigNoQuote()
    {
        $sql = $this->db->createSql();
        $sql->setIdQuoteType(Sql::NO_QUOTE);
        $this->assertNull($sql->getOpenQuote());
        $this->assertNull($sql->getCloseQuote());
        $this->db->disconnect();
    }

    public function testSelectWithValues()
    {
        $sql = $this->db->createSql();
        $sql->select([
            'id', 'username', 'email'
        ])->from('users');

        $this->assertEquals('users', $sql->select()->getTable());
        $this->assertEquals(3, count($sql->select()->getValues()));
        $this->assertEquals('id', $sql->select()->getValue(0));
        $this->assertEquals("SELECT `id`, `username`, `email` FROM `users`", $sql->render());
        $this->db->disconnect();
    }

    public function testSelectWithNamedValues()
    {
        $sql = $this->db->createSql();
        $sql->select([
            'id',
            'user_name' => 'username',
            'email'
        ])->from('users');
        $this->assertTrue($sql->hasSelect());
        $this->assertFalse($sql->hasInsert());
        $this->assertFalse($sql->hasUpdate());
        $this->assertFalse($sql->hasDelete());
        $this->assertEquals("SELECT `id`, `username` AS `user_name`, `email` FROM `users`", $sql->render());
        $this->db->disconnect();
    }

    public function testInsertWithTable()
    {
        $sql = $this->db->createSql();
        $sql->insert('users')->values([
            'username' => 'admin'
        ]);
        $this->assertFalse($sql->hasSelect());
        $this->assertTrue($sql->hasInsert());
        $this->assertFalse($sql->hasUpdate());
        $this->assertFalse($sql->hasDelete());
        $this->assertEquals("INSERT INTO `users` (`username`) VALUES ('admin')", (string)$sql);
        $this->db->disconnect();
    }

    public function testUpdate()
    {
        $sql = $this->db->createSql();
        $sql->update('users')->values(['username' => 'admin2'])->where('id = 1');
        $this->assertFalse($sql->hasSelect());
        $this->assertFalse($sql->hasInsert());
        $this->assertTrue($sql->hasUpdate());
        $this->assertFalse($sql->hasDelete());
        $this->assertEquals("UPDATE `users` SET `username` = 'admin2' WHERE (`id` = 1)", (string)$sql);
        $this->db->disconnect();
    }

    public function testDelete()
    {
        $sql = $this->db->createSql();
        $sql->delete('users')->where('id = 1');
        $this->assertFalse($sql->hasSelect());
        $this->assertFalse($sql->hasInsert());
        $this->assertFalse($sql->hasUpdate());
        $this->assertTrue($sql->hasDelete());
        $this->assertEquals("DELETE FROM `users` WHERE (`id` = 1)", (string)$sql);
        $this->db->disconnect();
    }

    public function testReset()
    {
        $sql = $this->db->createSql();
        $sql->delete('users')->where('id = 1');
        $this->assertFalse($sql->hasSelect());
        $this->assertFalse($sql->hasInsert());
        $this->assertFalse($sql->hasUpdate());
        $this->assertTrue($sql->hasDelete());
        $sql->reset();
        $this->assertFalse($sql->hasSelect());
        $this->assertFalse($sql->hasInsert());
        $this->assertFalse($sql->hasUpdate());
        $this->assertFalse($sql->hasDelete());

        $this->db->disconnect();
    }

    public function testRenderIsIdempotent()
    {
        $sql = $this->db->createSql();
        $sql->select(['id', 'username'])->from('users')->where('logins > :logins');

        $expected = 'SELECT `id`, `username` FROM `users` WHERE (`logins` > ?)';

        // Rendering must not consume the statement - a builder is commonly rendered once for
        // logging/inspection and then again to be executed
        $this->assertEquals($expected, $sql->render());
        $this->assertEquals($expected, $sql->render());
        $this->assertEquals($expected, (string)$sql);
        $this->assertTrue($sql->hasSelect());

        $this->db->disconnect();
    }

    public function testRenderIsIdempotentForInsertUpdateAndDelete()
    {
        $insert = $this->db->createSql();
        $insert->insert('users')->values(['username' => '?']);
        $this->assertEquals($insert->render(), $insert->render());
        $this->assertTrue($insert->hasInsert());

        $update = $this->db->createSql();
        $update->update('users')->values(['username' => '?'])->where('id = ?');
        $this->assertEquals($update->render(), $update->render());
        $this->assertTrue($update->hasUpdate());

        $delete = $this->db->createSql();
        $delete->delete('users')->where('id = ?');
        $this->assertEquals($delete->render(), $delete->render());
        $this->assertTrue($delete->hasDelete());

        $this->db->disconnect();
    }

    public function testRenderedStatementIsStillExecutableAfterBeingRenderedOnce()
    {
        $this->db->query('DROP TABLE IF EXISTS pop_sql_render');
        $this->db->query(
            'CREATE TABLE pop_sql_render (id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'username VARCHAR(255), logins INT)'
        );
        $this->db->query("INSERT INTO pop_sql_render (username, logins) VALUES ('admin', 5)");
        $this->db->query("INSERT INTO pop_sql_render (username, logins) VALUES ('guest', 1)");

        $sql = $this->db->createSql();
        $sql->select(['id', 'username'])->from('pop_sql_render')->where('logins > :logins');

        // First render simulates logging/inspecting the statement
        $logged = $sql->render();

        // Second render is the one that gets executed
        $this->db->prepare((string)$sql)
                 ->bindParams(['logins' => 2])
                 ->execute();

        $rows = $this->db->fetchAll();

        $this->assertEquals($logged, (string)$sql);
        $this->assertEquals(1, count($rows));
        $this->assertEquals('admin', $rows[0]['username']);

        $this->db->query('DROP TABLE IF EXISTS pop_sql_render');
        $this->db->disconnect();
    }

    public function testGetParameterTranslatesToSqliteNamedPlaceholder()
    {
        touch(__DIR__ . '/tmp/get_parameter.sqlite');
        $sqlite = Db::sqliteConnect(['database' => __DIR__ . '/tmp/get_parameter.sqlite']);
        $sql    = $sqlite->createSql();

        // A '?' token is MYSQL-shaped, but this Sql object is SQLITE-dialect, so
        // getParameter() must translate it into the dialect-correct ':column' token
        $this->assertEquals(':username', $sql->getParameter('?', 'username'));

        $sqlite->disconnect();
        @unlink(__DIR__ . '/tmp/get_parameter.sqlite');
    }

    public function testQuoteIdLeavesSupportedFunctionCallsUnquoted()
    {
        $sql = $this->db->createSql();

        // A supported SQL function call is an expression, not an identifier
        $this->assertEquals('COUNT(*)', $sql->quoteId('COUNT(*)'));
        $this->assertEquals('COUNT(1)', $sql->quoteId('COUNT(1)'));
        $this->assertEquals('SUM(total)', $sql->quoteId('SUM(total)'));
        $this->assertEquals('MAX(users.id)', $sql->quoteId('MAX(users.id)'));
        $this->assertEquals('COUNT(DISTINCT id)', $sql->quoteId('COUNT(DISTINCT id)'));

        $this->db->disconnect();
    }

    public function testQuoteIdStillQuotesEverythingThatIsNotAFunctionCall()
    {
        $sql = $this->db->createSql();

        $this->assertEquals('`username`', $sql->quoteId('username'));
        $this->assertEquals('`users`.`id`', $sql->quoteId('users.id'));
        // A bare function name with no argument list is just a column name
        $this->assertEquals('`count`', $sql->quoteId('count'));
        // An unknown function is not a supported expression
        $this->assertEquals('`nope(id)`', $sql->quoteId('nope(id)'));
        // Anything beyond a single, simple function call stays quoted, so a hostile column
        // name cannot escape identifier quoting
        $this->assertEquals('`COUNT(1) OR 1=1 -- `', $sql->quoteId('COUNT(1) OR 1=1 -- '));
        $this->assertEquals(
            "`COUNT(1),(SELECT password FROM admins)`", $sql->quoteId('COUNT(1),(SELECT password FROM admins)')
        );

        $this->db->disconnect();
    }

    public function testParameterCount()
    {
        $sql = $this->db->createSql();
        $this->assertEquals(0, $sql->getParameterCount());
        $sql->incrementParameterCount();
        $this->assertEquals(1, $sql->getParameterCount());
        $sql->decrementParameterCount();
        $this->assertEquals(0, $sql->getParameterCount());
    }

}