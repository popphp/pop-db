<?php

namespace Pop\Db\Test\Gateway;

use Pop\Db\Db;
use Pop\Db\Gateway;
use PHPUnit\Framework\TestCase;

class RowSqliteTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        if (file_exists(__DIR__ . '/../tmp/db.sqlite')) {
            unlink(__DIR__ . '/../tmp/db.sqlite');
        }
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/db.sqlite');
        chmod(__DIR__ . '/../tmp/db.sqlite', 0777);

        $this->db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/db.sqlite'
        ]);

        $schema = $this->db->createSchema();

        $schema->create('sq_users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\SqUsers::setDb($this->db);
    }

    public function testSaveAndFind()
    {
        $row = new Gateway\Row('sq_users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $newRow = new Gateway\Row('sq_users', ['id']);
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
    }

    public function testUpdate()
    {
        $row = new Gateway\Row('sq_users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $row = new Gateway\Row('sq_users', ['id']);
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

        $newRow = new Gateway\Row('sq_users', ['id']);
        $newRow->find(1);

        $this->assertTrue(isset($newRow->id));
        $this->assertEquals(1, $newRow->id);
        $this->assertTrue(isset($newRow->username));
        $this->assertEquals('testuser2', $newRow->username);
        $this->assertTrue(isset($newRow->password));
        $this->assertEquals('987654', $newRow->password);
    }

    public function testDelete()
    {
        $row = new Gateway\Row('sq_users', ['id']);
        $row->save([
            'username' => 'testuser1',
            'password' => '123456'
        ]);
        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row['id']);

        $row = new Gateway\Row('sq_users', ['id']);
        $row->find(1);

        $this->assertTrue(isset($row->id));
        $this->assertEquals(1, $row->id);
        $this->assertTrue(isset($row->username));
        $this->assertEquals('testuser1', $row->username);
        $this->assertTrue(isset($row->password));
        $this->assertEquals('123456', $row->password);

        $row->delete();

        $newRow = new Gateway\Row('sq_users', ['id']);
        $newRow->find(1);

        $this->assertFalse(isset($newRow->id));

        $newRow2 = new Gateway\Row('sq_users', ['id']);
        $newRow2->id = null;
        $newRow2->setPrimaryValues([null]);
        $newRow2->delete();
        $this->assertFalse(isset($newRow2->id));

        $schema = $this->db->createSchema();
        $schema->dropIfExists('sq_users');
        $schema->execute();

        $this->db->disconnect();
        unlink(__DIR__ . '/../tmp/db.sqlite');
    }

}