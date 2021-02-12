<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{

    public function testConnectException()
    {
        $this->expectException('Pop\Db\Exception');
        $db = Db::connect('mysql', [], 'Bad\Namespace\\');
    }

    public function testCheck()
    {
        $check = Db::check('mysql', [
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1'
        ]);
        $this->assertTrue(($check === true));
    }

    public function testCheckError()
    {
        $check = Db::check('mysql', [
            'database' => 'bad_db',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1'
        ]);
        $this->assertStringContainsString('Error: ', $check);
    }

    public function testCheckException()
    {
        $check = Db::check('mysql', [
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1'
        ], 'Bad\Namespace\\');
        $this->assertEquals("Error: The database adapter 'Bad\Namespace\Mysql' does not exist.", $check);
    }

    public function testExecuteSqlException()
    {
        $this->expectException('Pop\Db\Exception');
        Db::executeSqlFile(__DIR__ . '/tmp/users.mysql.sql', 'mysql', [
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
            'prefix'   => 'pop_'
        ], 'Bad\NameSpace\\');
    }

    public function testExecuteSqlFileException()
    {
        $this->expectException('Pop\Db\Exception');
        Db::executeSqlFile(__DIR__ . '/tmp/bad.mysql.sql', 'mysql', [
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
            'prefix'   => 'pop_'
        ]);
    }

    public function testExecuteMysqlSqlFile()
    {
        Db::executeSqlFile(__DIR__ . '/tmp/users.mysql.sql', 'mysql', [
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
            'prefix'   => 'pop_'
        ]);
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
        ]);
        $this->assertTrue($db->hasTable('pop_users'));
        $db->query('DROP TABLE `pop_users`');

        $db->disconnect();
    }

    public function testExecuteMysqlSqlFileWithAdapter()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
        ]);
        Db::executeSqlFile(__DIR__ . '/tmp/users.mysql.sql', $db, ['prefix'   => 'pop_']);
        $this->assertTrue($db->hasTable('pop_users'));
        $db->query('DROP TABLE `pop_users`');

        $db->disconnect();
    }

    public function testExecuteSqliteSqlFile()
    {
        chmod(__DIR__ . '/tmp', 0777);

        Db::executeSqlFile(__DIR__ . '/tmp/users.sqlite.sql', 'sqlite', ['database' => __DIR__ . '/tmp/db.sqlite', 'prefix' => 'pop_']);
        $db = Db::sqliteConnect(['database' => __DIR__ . '/tmp/db.sqlite']);
        $this->assertTrue($db->hasTable('pop_users'));

        unlink(__DIR__ . '/tmp/db.sqlite');
    }

    public function testGetAvailableAdapters()
    {
        $adapters = Db::getAvailableAdapters();
        $this->assertTrue(isset($adapters['mysqli']));
        $this->assertTrue(isset($adapters['pdo']));
        $this->assertTrue(isset($adapters['pdo']['mysql']));
        $this->assertTrue(isset($adapters['pdo']['pgsql']));
        $this->assertTrue(isset($adapters['pdo']['sqlite']));
        $this->assertTrue(isset($adapters['pdo']['sqlsrv']));
        $this->assertTrue(isset($adapters['pgsql']));
        $this->assertTrue(isset($adapters['sqlite']));
        $this->assertTrue(isset($adapters['sqlsrv']));
    }

    public function testIsAvailable()
    {
        $this->assertIsBool(Db::isAvailable('mysql'));
        $this->assertIsBool(Db::isAvailable('mysqli'));
        $this->assertIsBool(Db::isAvailable('pgsql'));
        $this->assertIsBool(Db::isAvailable('sqlite'));
        $this->assertIsBool(Db::isAvailable('sqlsrv'));
        $this->assertIsBool(Db::isAvailable('pdo_mysql'));
        $this->assertIsBool(Db::isAvailable('pdo_pgsql'));
        $this->assertIsBool(Db::isAvailable('pdo_sqlite'));
        $this->assertIsBool(Db::isAvailable('pdo_sqlsrv'));
    }

    public function testSetDbByClassPrefix()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
        ]);
        Db::setDb($db, null, 'Pop\Db\Test\TestAsset\\');
        Db::addClassToTable('Pop\Db\Test\TestAsset\Users', 'users');
        $this->assertTrue(Db::hasDb('Pop\Db\Test\TestAsset\Users'));
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Db::db('Pop\Db\Test\TestAsset\Users'));
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Db::db('users'));

        $db->disconnect();
    }

    public function testSetDbByClass()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/tmp/.mysql')),
            'host'     => '127.0.0.1',
        ]);
        Db::setDb($db, 'Pop\Db\Test\TestAsset\Users', null, true);
        Db::setDefaultDb($db, 'Pop\Db\Test\TestAsset\Users');
        $this->assertTrue(Db::hasDb('Pop\Db\Test\TestAsset\Users'));
        $this->assertTrue(Db::hasDb());
        $this->assertTrue(Db::hasDb('users'));
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Db::db());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Db::db('users'));
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Db::db('Pop\Db\Test\TestAsset\Users'));
        $this->assertTrue(is_array(Db::getAll()));

        $db->disconnect();
    }

}