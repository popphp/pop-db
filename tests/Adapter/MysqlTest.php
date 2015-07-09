<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Db;
use Pop\Db\Adapter\Mysql;

class MysqlTest extends \PHPUnit_Framework_TestCase
{

    protected $password = '12root34';

    public function testConstructorException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'username' => 'root',
            'password' => $this->password
        ]);
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
        $this->assertContains('MySQL', $db->version());
    }

    public function testExecuteException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->execute();
    }

    public function testFetchException()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->fetch();
    }

    public function testShowError()
    {
        $this->setExpectedException('Pop\Db\Adapter\Exception');
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->showError();
    }

    public function testIsInstalled()
    {
        $this->assertTrue(Mysql::isInstalled());
    }

    public function testLoadTables()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
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
        $db->prepare('INSERT INTO ph_users (`username`, `email`) VALUES (?, ?)')
           ->bindParams(['testuser', $db->escape('test@test.com')])
           ->execute();


        $this->assertFalse($db->hasResult());
        $this->assertNull($db->getResult());
        $this->assertNotNull($db->lastId());
        $this->assertNotNull($db->getConnection());
        $this->assertEquals(0, $db->numberOfRows());
        $this->assertEquals(0, $db->numberOfFields());
    }

    public function testFetch()
    {
        $db = new Mysql([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $db->query('SELECT * FROM ph_users');

        $rows = [];

        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->numberOfRows());
        $this->assertEquals(6, $db->numberOfFields());

        $db->disconnect();
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

        $rows = $db->fetchResult();
        $this->assertEquals(1, count($rows));
        $this->assertEquals(1, $db->numberOfRows());
        $this->assertEquals(6, $db->numberOfFields());

        $db->disconnect();
    }

}
