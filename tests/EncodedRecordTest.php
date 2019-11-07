<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Record;
use PHPUnit\Framework\TestCase;

class EncodedRecordTest extends TestCase
{

    public function testSetDbException()
    {
        $this->expectException('Pop\Db\Exception');
        $db = TestAsset\Users::db();
    }

    public function testSetDb()
    {
        $db = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        Record::setDb($db, true);
        TestAsset\EncodedUsers::setDb($db, true);
        TestAsset\EncodedUsers::setDefaultDb($db);
        $this->assertTrue(TestAsset\Users::hasDb());
        $this->assertEquals('ph_encoded_users', TestAsset\EncodedUsers::table());
        $this->assertInstanceOf('Pop\Db\Adapter\Sqlite', TestAsset\EncodedUsers::db());
        $this->assertInstanceOf('Pop\Db\Sql', TestAsset\EncodedUsers::getSql());
        $this->assertInstanceOf('Pop\Db\Sql', TestAsset\EncodedUsers::sql());
    }

    public function testDecode()
    {
        $user = new TestAsset\EncodedUsers();

        $decodedColumns = $user->decode([
            'info'     => json_encode(['foo' => 'bar']),
            'metadata' => ['test' => 123],
            'encoded'  => base64_encode('Some text from a file')
        ]);

        $this->assertTrue($decodedColumns['info'] == ['foo' => 'bar']);
        $this->assertTrue($decodedColumns['metadata'] == ['test' => 123]);
        $this->assertEquals('Some text from a file', $decodedColumns['encoded']);
    }

    public function testSaveFindAndDelete()
    {
        $user = new TestAsset\EncodedUsers([
            'username' => 'testuser',
            'password' => '12test34',
            'info'     => ['foo' => 'bar'],
            'metadata' => ['test' => 123],
            'encoded'  => 'Some text from a file',
            'ssn'      => '123-45-6789'
        ]);

        $user->save();

        $id = $user->id;

        $u = TestAsset\EncodedUsers::findById($id);
        $userAry = $u->toArray();
        $this->assertTrue($u->verify('password', '12test34'));
        $this->assertTrue($u->info == ['foo' => 'bar']);
        $this->assertTrue($u->metadata == ['test' => 123]);
        $this->assertEquals('Some text from a file', $u->encoded);
        $this->assertEquals('123-45-6789', $u->ssn);

        $this->assertTrue($userAry['info'] == ['foo' => 'bar']);
        $this->assertTrue($userAry['metadata'] == ['test' => 123]);
        $this->assertEquals('Some text from a file', $userAry['encoded']);
        $this->assertEquals('123-45-6789', $userAry['ssn']);

        $editUser = TestAsset\EncodedUsers::findById($id);
        $editUser->password = 'newpassword';
        $editUser->save();
        $this->assertTrue($editUser->verify('password', 'newpassword'));

        TestAsset\EncodedUsers::db()->disconnect();
    }

}