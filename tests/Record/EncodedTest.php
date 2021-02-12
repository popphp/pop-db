<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\UsersEncoded;
use Pop\Db\Test\TestAsset\UsersEncoded2;
use PHPUnit\Framework\TestCase;

class EncodedTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => 'localhost'
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('users_encoded');
        $schema->execute();

        $schema->create('users_encoded')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->text('info')
            ->text('notes')
            ->text('file')
            ->text('ssn')
            ->primary('id');

        $schema->execute();

        \Pop\Db\Test\TestAsset\UsersEncoded::setDb($this->db);
    }

    public function testEncodeException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncoded2([
            'ssn' => '123-45-6789'
        ]);
    }

    public function testDecodeException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncoded2();
        $ssn  = $user->ssn;
    }

    public function testSaveAndFind()
    {
        $user = new UsersEncoded([
            'username' => 'testuser',
            'password' => '12test34',                       // HASH
            'email'    => 'testuser@test.com',
            'info'     => ['foo' => 'bar'],                 // JSON
            'notes'    => ['birthdate' => '08/19/77'],      // PHP
            'file'     => 'some really long binary string', // BASE 64
            'ssn'      => '123-45-6789',                    // ENCRYPTED
        ]);

        $user->save();

        $user = UsersEncoded::findById($user->id);

        $info  = $user->info;
        $notes = $user->notes;
        $file  = $user->file;
        $ssn   = $user->ssn;


        $this->assertIsArray($info);
        $this->assertEquals('bar', $info['foo']);
        $this->assertIsArray($notes);
        $this->assertEquals('08/19/77', $notes['birthdate']);
        $this->assertEquals('some really long binary string', $file);
        $this->assertEquals('123-45-6789', $ssn);
        $this->assertTrue($user->verify('password', '12test34'));
        $this->assertFalse($user->verify('password', 'bad'));

        $this->assertIsArray($user->toArray());

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users_encoded');
        $schema->execute();

        $this->db->disconnect();
    }

    public function testSetColumns()
    {
        $user1 = UsersEncoded::findById(1);
        $user2 = new UsersEncoded();
        $user2->setColumns($user1);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\UsersEncoded', $user1);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\UsersEncoded', $user2);

        $this->db->disconnect();
    }

    public function testSetColumnsException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncoded();
        $user->setColumns('bad');
    }

    public function testSetter()
    {
        $user = new UsersEncoded([
            'username' => 'testuser',
            'password' => '12test34',                       // HASH
            'email'    => 'testuser@test.com',
            'info'     => ['foo' => 'bar'],                 // JSON
            'notes'    => ['birthdate' => '08/19/77'],      // PHP
            'file'     => 'some really long binary string', // BASE 64
            'ssn'      => '123-45-6789',                    // ENCRYPTED
        ]);

        $user->save();

        $user->username = 'testuser2';
        $user->password = '123456';                     // HASH
        $user->email    = 'testuser2@test.com';
        $user->info     = ['foo2' => 'bar2'];           // JSON
        $user->notes    = ['birthdate' => '01/01/77'];  // PHP
        $user->file     = 'another long binary string'; // BASE 64
        $user->ssn      = '987-65-4321';                // ENCRYPTED

        $user->save();

        $user = UsersEncoded::findById($user->id);

        $info  = $user->info;
        $notes = $user->notes;
        $file  = $user->file;
        $ssn   = $user->ssn;

        $this->assertIsArray($info);
        $this->assertEquals('bar2', $info['foo2']);
        $this->assertIsArray($notes);
        $this->assertEquals('01/01/77', $notes['birthdate']);
        $this->assertEquals('another long binary string', $file);
        $this->assertEquals('987-65-4321', $ssn);
        $this->assertTrue($user->verify('password', '123456'));

        $this->db->disconnect();
    }

    public function testDecode()
    {
        $encoded = [
            'info'     => '{"foo":"bar"}',
            'notes'    => 'a:1:{s:9:"birthdate";s:8:"08/19/77";}',
            'file'     => 'c29tZSByZWFsbHkgbG9uZyBiaW5hcnkgc3RyaW5n',
            'ssn'      => 'RQ4O1riQb6Vwr9wz32X9Lg=='
        ];

        $decoded = (new UsersEncoded())->decode($encoded);

        $this->assertIsArray($decoded['info']);
        $this->assertEquals('bar', $decoded['info']['foo']);
        $this->assertIsArray($decoded['notes']);
        $this->assertEquals('08/19/77', $decoded['notes']['birthdate']);
        $this->assertEquals('some really long binary string', $decoded['file']);
        $this->assertEquals('123-45-6789', $decoded['ssn']);

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users_encoded');
        $schema->execute();

        $this->db->disconnect();

    }

}