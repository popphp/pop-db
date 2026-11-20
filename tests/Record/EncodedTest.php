<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\UsersEncoded;
use Pop\Db\Test\TestAsset\UsersEncoded2;
use Pop\Db\Test\TestAsset\UsersEncodedWeakHash;
use Pop\Db\Test\TestAsset\UsersEncodedJsonKeys;
use Pop\Db\Test\TestAsset\UsersEncodedJsonKeysNotJson;
use Pop\Crypt\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class EncodedTest extends TestCase
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
        UsersEncodedJsonKeys::setDb($this->db);
    }

    public function testEncodeException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncoded2([
            'ssn' => '123-45-6789'
        ]);
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

    public function testNeedsRehashAndRehash()
    {
        UsersEncodedWeakHash::setDb($this->db);

        // Seed a row whose password was hashed with a deliberately weak/outdated cost
        $weakUser = new UsersEncodedWeakHash([
            'username' => 'weakhash',
            'password' => 'insecurepw',
        ]);
        $weakUser->save();

        $oldHash = $weakUser->getRawValue('password');

        // Verify through the normally-configured class (stronger cost)
        $user = UsersEncoded::findById($weakUser->id);

        $this->assertFalse($user->verify('password', 'wrong-password'));
        $this->assertFalse($user->needsRehash());

        // Pass autoRehash: false to observe the flag before rehashing manually
        $this->assertTrue($user->verify('password', 'insecurepw', false));
        $this->assertTrue($user->needsRehash());

        $user->rehash('password', 'insecurepw');
        $this->assertFalse($user->needsRehash());
        $this->assertNotEquals($oldHash, $user->getRawValue('password'));

        $reloaded = UsersEncoded::findById($user->id);
        $this->assertTrue($reloaded->verify('password', 'insecurepw'));

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users_encoded');
        $schema->execute();

        $this->db->disconnect();
    }

    public function testDecodeValueThrowsWhenEncryptionPropertiesNotSet()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncoded2();
        $user->decodeValue('ssn', 'some-encrypted-looking-value');
    }

    public function testEncryptionPropertiesLoadedFromEnvWithPreviousKeys()
    {
        $_ENV['APP_CIPHER_METHOD'] = 'aes-256-cbc';
        $_ENV['APP_KEY']           = 'vBTcBMBrauIpjy2oXhXOFxshW4//tXXnagOr2a+AqKI=';
        $_ENV['APP_PREVIOUS_KEYS'] = '3ODbtHLqzlnAiB0yZR7E0w6wdnIiB35Mj6An+SHwIwg=';

        try {
            // UsersEncoded2 has no cipherMethod/key of its own, so both encodeValue()
            // and decodeValue() must fall back to loadEncryptionProperties() to pull
            // the cipher, key, and previous keys from $_ENV
            $user      = new UsersEncoded2(['ssn' => '123-45-6789']);
            $encrypted = $user->getRawValue('ssn');
            $this->assertNotEquals('123-45-6789', $encrypted);

            $decoded = $user->decodeValue('ssn', $encrypted);
            $this->assertEquals('123-45-6789', $decoded);
        } finally {
            unset($_ENV['APP_CIPHER_METHOD'], $_ENV['APP_KEY'], $_ENV['APP_PREVIOUS_KEYS']);
        }
    }

    public function testDecode()
    {
        $encoded = [
            'info'     => '{"foo":"bar"}',
            'notes'    => 'a:1:{s:9:"birthdate";s:8:"08/19/77";}',
            'file'     => 'c29tZSByZWFsbHkgbG9uZyBiaW5hcnkgc3RyaW5n',
            'ssn'      => 'eyJpdiI6ImJHMlRma1o1VmkvM3FRbEgzeGZWaGc9PSIsInZhbHVlIjoiQ0pzTEZXUzVQRXlveEtic1c4ZmpOUT09IiwibWFjIjoiOWFkYzNmOGM3OTgzMGQ1NWE0MTM1NzA1YzhlZjJjMmZjYjNjZGEwMWU5NmM2MWY3ZDlkMGE1YzczYzE4Y2ViNSIsInRhZyI6IiJ9'
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

    public function testEncryptedJsonKeysGetter()
    {
        $this->assertEquals(['info' => ['secret', 'pin']], (new UsersEncodedJsonKeys())->getEncryptedJsonKeys());
    }

    public function testEncryptedJsonKeysSetterEncryptsOnlyListedKeys()
    {
        $user       = new UsersEncodedJsonKeys();
        $user->info = ['secret' => 's3cret', 'name' => 'bob'];

        $raw = json_decode($user->getRawValue('info'), true);
        $this->assertEquals('bob', $raw['name']);
        $this->assertNotEquals('s3cret', $raw['secret']);
        $this->assertEquals('s3cret', $user->info['secret']);
        $this->assertEquals('bob', $user->info['name']);
    }

    public function testEncryptedJsonKeysJsonStringInput()
    {
        $user       = new UsersEncodedJsonKeys();
        $user->info = '{"secret":"s3cret"}';

        $raw = json_decode($user->getRawValue('info'), true);
        $this->assertNotEquals('s3cret', $raw['secret']);
        $this->assertEquals('s3cret', $user->info['secret']);
    }

    public function testEncryptedJsonKeysSaveFindAndToArray()
    {
        $user = new UsersEncodedJsonKeys([
            'username' => 'jk',
            'info'     => ['secret' => 's3cret', 'name' => 'bob']
        ]);
        $user->save();

        $found = UsersEncodedJsonKeys::findById($user->id);
        $this->assertEquals('s3cret', $found->info['secret']);
        $this->assertEquals('bob', $found->info['name']);
        $this->assertEquals('s3cret', $found->toArray()['info']['secret']);

        $this->db->query('SELECT `info` FROM `users_encoded` WHERE `id` = ' . (int)$user->id);
        $row = $this->db->fetch();
        $this->assertStringNotContainsString('s3cret', $row['info']);
        $this->assertStringContainsString('bob', $row['info']);

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users_encoded');
        $schema->execute();

        $this->db->disconnect();
    }

    public function testEncryptedJsonKeysNoDoubleEncryption()
    {
        $user       = new UsersEncodedJsonKeys();
        $user->info = ['secret' => 's3cret'];
        $stored     = $user->getRawValue('info');
        $cipher     = json_decode($stored, true)['secret'];

        $user->info = $stored;
        $this->assertEquals($cipher, json_decode($user->getRawValue('info'), true)['secret']);
        $this->assertEquals('s3cret', $user->info['secret']);

        $encoded = $user->encodeValue('info', json_decode($stored, true));
        $this->assertEquals($cipher, json_decode($encoded, true)['secret']);
    }

    public function testEncryptedJsonKeysJsonStringKeptAsIsWhenNothingToEncrypt()
    {
        $user   = new UsersEncodedJsonKeys();
        $user->info = ['secret' => 's3cret'];
        $cipher = json_decode($user->getRawValue('info'), true)['secret'];

        // As stored in (and loaded from) the database: an empty object, an unescaped slash and an
        // already-encrypted secret must all come back byte-identical
        $stored     = '{"opts": {}, "url": "http://a/b", "secret": "' . $cipher . '"}';
        $user->info = $stored;
        $this->assertSame($stored, $user->getRawValue('info'));

        $user->setColumns(['info' => '{"opts": {}}']);
        $this->assertSame('{"opts": {}}', $user->getRawValue('info'));
    }

    public function testEncryptedJsonKeysSkipNonStringAndEmpty()
    {
        $user       = new UsersEncodedJsonKeys();
        $user->info = ['secret' => '', 'pin' => 1234];
        $this->assertSame(['secret' => '', 'pin' => 1234], json_decode($user->getRawValue('info'), true));
        $this->assertSame(['secret' => '', 'pin' => 1234], $user->info);

        $user->info = ['name' => 'bob'];
        $this->assertSame(['name' => 'bob'], json_decode($user->getRawValue('info'), true));
        $this->assertSame(['name' => 'bob'], $user->info);
    }

    public function testEncryptedJsonKeysPreviousKey()
    {
        $oldKey = '3ODbtHLqzlnAiB0yZR7E0w6wdnIiB35Mj6An+SHwIwg=';
        $old    = (new Encrypter($oldKey, 'aes-256-cbc', false))->encrypt('s3cret');
        $user   = new class([], $oldKey) extends UsersEncodedJsonKeys {
            public function __construct(array $columns, string $previousKey)
            {
                $this->previousKeys = [$previousKey];
                parent::__construct($columns);
            }
        };

        $this->assertEquals('s3cret', $user->decodeValue('info', json_encode(['secret' => $old]))['secret']);
    }

    public function testEncryptedJsonKeysColumnNotJsonThrows()
    {
        UsersEncodedJsonKeysNotJson::setDb($this->db);

        $this->expectException('Pop\Db\Record\Exception');
        $user = new UsersEncodedJsonKeysNotJson();
        $user->notes = ['secret' => 'x'];
    }

}
