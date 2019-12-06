<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{

    protected $db = null;

    public function setUp()
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => 'localhost'
        ]);
    }

    public function testConstructor()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->setPrimaryValues([1]);
        $this->assertInstanceOf('Pop\Db\Gateway\Row', $row);
        $this->assertEquals(1, count($row->getPrimaryKeys()));
        $this->assertEquals(1, count($row->getPrimaryValues()));
        $this->assertTrue($row->doesPrimaryCountMatch());
        $this->db->disconnect();
    }

    public function testPrimaryMatchException()
    {
        $this->expectException('Pop\Db\Gateway\Exception');
        $row = new Gateway\Row('users', ['id']);
        $row->setPrimaryValues([1, 2]);
        $this->assertTrue($row->doesPrimaryCountMatch());
    }

    public function testSetAndGetColumns()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->setColumns([
            'id'       => 1,
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $columns        = $row->getColumns();
        $columnsToArray = $row->toArray();
        $this->assertTrue(($columns === $columnsToArray));
        $this->assertEquals(1, $columns['id']);
        $this->assertEquals('testuser1', $columns['username']);
        $this->assertEquals('password1', $columns['password']);
        $this->assertEquals(3, $row->count());

        $string = '';
        $i      = 0;
        foreach ($row as $column => $value) {
            $string .= $value;
            $i++;
        }

        $this->assertEquals('1testuser1password1', $string);
        $this->assertEquals(3, $i);
    }

    public function testGetDirty()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->resetDirty();
        $row->username   = 'testuser1';
        $row['username'] = 'testuser2';
        $this->assertTrue($row->isDirty());
        $this->assertTrue(is_array($row->getDirty()));
        $dirty = $row->getDirty();
        $this->assertEquals('testuser1', $dirty['old']['username']);
        $this->assertEquals('testuser2', $dirty['new']['username']);
    }


    public function testSettersAndGetters()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->username   = 'testuser1';
        $row['password'] = 'password1';
        $this->assertTrue(isset($row->username));
        $this->assertTrue(isset($row['password']));
        $this->assertEquals('testuser1', $row->username);
        $this->assertEquals('password1', $row['password']);
        unset($row->username);
        unset($row['password']);
        $this->assertFalse(isset($row->username));
        $this->assertFalse(isset($row['password']));
    }

}