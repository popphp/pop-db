<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Sql;
use Pop\Db\Adapter\Mysql;
use Pop\Db\Adapter\Pdo;

class MysqlTest extends \PHPUnit_Framework_TestCase
{

    protected $password = '';

    public function testConstructorException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'username' => 'root',
            'password' => $this->password
        ]);
    }

    public function testMysqlConnect()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $db);
    }

    public function testPdoMysql()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password,
            'type'     => 'mysql',
            'options'  => [\PDO::ATTR_PERSISTENT => false]
        ]);
        $this->assertInstanceOf('Pop\Db\Adapter\Pdo', $db);
    }

    public function testConstructor()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);

        $db->query('DROP TABLE IF EXISTS `ph_users`');

        $table = <<<TABLE
CREATE TABLE IF NOT EXISTS `ph_users` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` int(1),
  `verified` int(1),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1001
TABLE;
        $db->query($table);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', $db);
        $this->assertContains('MySQL', $db->getVersion());
    }

    public function testExecuteException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->clearError();
        $this->assertFalse($db->hasError());
        $db->throwError('Error: Some Error');
    }

    public function testGetTables()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $this->assertContains('ph_users', $db->getTables());
        $this->assertTrue($db->hasTable('ph_users'));
    }

    public function testGetTablesFromPdo()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password,
            'type'     => 'mysql'
        ]);
        $this->assertContains('ph_users', $db->getTables());
    }

    public function testBindParams()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->prepare('INSERT INTO ph_users (`username`, `password`, `email`) VALUES (?, ?, ?)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();

        $sql = new Sql($db, 'ph_users');
        $this->assertEquals(Sql::MYSQL, $sql->getDbType());

        $this->assertNull($db->getResult());
        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->getNumberOfRows());
    }

    public function testBindParamsWithPdo()
    {
        $db = new Pdo([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password,
            'type'     => 'mysql'
        ]);

        $sql = new Sql($db, 'ph_users');
        $this->assertEquals('`value`', $sql->quoteId('value'));
        $this->assertEquals(Sql::MYSQL, $sql->getDbType());

        $db->prepare('INSERT INTO ph_users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();

        $this->assertNotNull($db->getLastId());
        $this->assertNotNull($db->getConnection());
    }

    public function testFetch()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->query('SELECT * FROM ph_users');
        $this->assertTrue($db->hasResult());
        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(2, count($rows));
        $this->assertEquals(2, $db->getNumberOfRows());
    }

    public function testFetchResults()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->prepare('SELECT * FROM ph_users WHERE id != ?')
           ->bindParams([0])
           ->execute();

        $rows = $db->fetchAll();
        $this->assertTrue($db->hasStatement());
        $this->assertInstanceOf('mysqli_stmt', $db->getStatement());
        $this->assertEquals(2, count($rows));
        $this->assertEquals(2, $db->getNumberOfRows());
        $this->assertNull($db->getError());

        $db->disconnect();
    }

    public function testTransaction()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);

        $db->beginTransaction();
        $db->prepare('INSERT INTO ph_users (`username`, `password`, `email`) VALUES (?, ?, ?)')
           ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
           ->execute();
        $db->commit();

        $db->prepare('SELECT * FROM ph_users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(3, $db->getNumberOfRows());
    }

    public function testRollback()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);

        $db->beginTransaction();
        $db->prepare('INSERT INTO ph_users (`username`, `password`, `email`) VALUES (?, ?, ?)')
            ->bindParams(['testuser', '12test34', $db->escape('test@test.com')])
            ->execute();
        $db->rollback();

        $db->prepare('SELECT * FROM ph_users WHERE id != ?')
            ->bindParams([0])
            ->execute();

        $this->assertEquals(3, $db->getNumberOfRows());
    }

}
