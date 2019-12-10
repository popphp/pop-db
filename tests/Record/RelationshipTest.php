<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\People;
use Pop\Db\Test\TestAsset\PeopleInfo;
use Pop\Db\Test\TestAsset\PeopleContacts;
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
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
        $schema->disableForeignKeyCheck();
        $schema->dropIfExists('people');
        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        $schema->create('people')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->primary('id');

        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();
        $schema->disableForeignKeyCheck();
        $schema->dropIfExists('people_info');
        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        $schema->create('people_info')
            ->int('people_id', 16)
            ->text('metadata')
            ->text('notes')
            ->foreignKey('people_id', 'fk_info_people_id')->references('people')->on('id')->onDelete('CASCADE');

        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();
        $schema->disableForeignKeyCheck();
        $schema->dropIfExists('people_contacts');
        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        $schema->create('people_contacts')
            ->int('people_id', 16)
            ->varchar('email', 255)
            ->foreignKey('people_id', 'fk_contacts_people_id')->references('people')->on('id')->onDelete('CASCADE');

        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        People::setDb($this->db);
        PeopleInfo::setDb($this->db);
        PeopleContacts::setDb($this->db);

        $user = new People([
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $user->save();

        $this->db->disconnect();
        $this->db->connect();

        $userInfo = new PeopleInfo([
            'people_id'  => $user->id,
            'metadata' => 'Some People Meta Data',
            'notes'    => 'Some People Notes'
        ]);

        $userInfo->save();

        $this->db->disconnect();
        $this->db->connect();

        $userContact = new PeopleContacts([
            'people_id' => $user->id,
            'email'   => 'testuser1@test.com'
        ]);

        $userContact->save();

        $this->db->disconnect();
        $this->db->connect();

        $userContact = new PeopleContacts([
            'people_id' => $user->id,
            'email'   => 'testuser1_alt@gmail.com'
        ]);

        $userContact->save();
        $this->db->disconnect();
    }

    public function testGetRelationships()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);
        $info     = $user->peopleInfo();
        $contacts = $user->peopleContacts();
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $info);
        $this->assertEquals('Some People Meta Data', $info->metadata);
        $this->assertEquals('Some People Notes', $info->notes);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $contacts);
        $this->assertEquals(2, $contacts->count());
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleContacts', $contacts[0]);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleContacts', $contacts[1]);
        $this->assertEquals('testuser1@test.com', $contacts[0]->email);
        $this->assertEquals('testuser1_alt@gmail.com', $contacts[1]->email);

        $parent = $info->parent();
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $parent);
        $this->assertEquals('testuser1', $parent->username);
        $this->db->disconnect();
    }

    public function testGetRelationshipsWith()
    {
        $this->db->connect();
        $user = People::with('peopleContacts');
        $this->assertInstanceOf('Pop\Db\Record\Collection', $user['peopleContacts']);
    }

    public function testFinal()
    {
        $var = 1;
        $this->assertEquals(1, $var);

        $this->db->connect();

        $schema = $this->db->createSchema();

        $this->db->query('SET foreign_key_checks = 0');

        $this->db->query('ALTER TABLE `people_info` DROP FOREIGN KEY `fk_info_people_id`');

        $this->db->query('SET foreign_key_checks = 0');

        $schema->dropIfExists('people_info');
        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        $this->db->query('SET foreign_key_checks = 0');

        $this->db->query('ALTER TABLE `people_contacts` DROP FOREIGN KEY `fk_contacts_people_id`');

        $this->db->query('SET foreign_key_checks = 0');

        $schema->dropIfExists('people_contacts');
        $schema->execute();

        $this->db->disconnect();
        $this->db->connect();

        $this->db->query('SET foreign_key_checks = 0');

        $schema->dropIfExists('people');
        $schema->execute();

        $this->db->disconnect();
    }

}