<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class RowMysqlTest extends TestCase
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

        $schema = $this->db->createSchema();

        $schema->dropIfExists('users');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\Users::setDb($this->db);
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
        $this->db->disconnect();
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
        $this->db->disconnect();
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
        $this->db->disconnect();
    }

    public function testFindException()
    {
        $this->expectException('Pop\Db\Gateway\Exception');
        $row = new Gateway\Row('users');
        $row->find(1);
    }

    public function testSaveAndFind()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $newRow = new Gateway\Row('users', ['id']);
        $newRow->find(1);

        $this->assertTrue(isset($newRow->id));
        $this->assertEquals(1, $newRow->id);
        $this->assertTrue(isset($newRow->username));
        $this->assertEquals('testuser1', $newRow->username);
        $this->assertTrue(isset($newRow->password));
        $this->assertEquals('123456', $newRow->password);

        $newRow->find(1, ['username']);
        $this->assertFalse(isset($newRow->id));
        $this->assertTrue(isset($newRow->username));
        $this->assertEquals('testuser1', $newRow->username);
        $this->assertFalse(isset($newRow->password));

        $newRow->find(null);
        $this->assertFalse(isset($newRow->id));
        $this->db->disconnect();
    }

    public function testUpdate()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $row = new Gateway\Row('users', ['id']);
        $row->find(1);

        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row->id);
        $this->assertTrue(isset($row->username));
        $this->assertEquals('testuser1', $row->username);
        $this->assertTrue(isset($row->password));
        $this->assertEquals('123456', $row->password);

        $row->username = 'testuser2';
        $row->password = '987654';
        $row->update();

        $newRow = new Gateway\Row('users', ['id']);
        $newRow->find(1);

        $this->assertTrue(isset($newRow->id));
        $this->assertEquals(1, $newRow->id);
        $this->assertTrue(isset($newRow->username));
        $this->assertEquals('testuser2', $newRow->username);
        $this->assertTrue(isset($newRow->password));
        $this->assertEquals('987654', $newRow->password);
        $this->db->disconnect();
    }

    public function testDeleteException()
    {
        $this->expectException('Pop\Db\Gateway\Exception');
        $row = new Gateway\Row('users');
        $row->delete();
        $this->db->disconnect();
    }

    public function testDelete()
    {
        $row = new Gateway\Row('users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $row = new Gateway\Row('users', ['id']);
        $row->find(1);

        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row->id);
        $this->assertTrue(isset($row->username));
        $this->assertEquals('testuser1', $row->username);
        $this->assertTrue(isset($row->password));
        $this->assertEquals('123456', $row->password);

        $row->delete();

        $newRow = new Gateway\Row('users', ['id']);
        $newRow->find(1);

        $this->assertFalse(isset($newRow->id));

        $newRow2 = new Gateway\Row('users', ['id']);
        $newRow2->id = null;
        $newRow2->setPrimaryValues([null]);
        $newRow2->delete();
        $this->assertFalse(isset($newRow2->id));

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $schema->execute();

        $this->db->disconnect();
    }

}