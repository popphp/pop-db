<?php

namespace Pop\Db\Test;

use Pop\Db\Adapter\Profiler\Profiler;
use Pop\Db\Db;
use Pop\Db\Record\Relationships;
use Pop\Db\Test\TestAsset\People;
use Pop\Db\Test\TestAsset\PeopleInfo;
use Pop\Db\Test\TestAsset\PeopleContacts;
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
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

    public function testBelongRelationship()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);
        $user = $info->parent();
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $user);
        $this->db->disconnect();
    }

    public function testGetRelationshipsWith1()
    {
        $this->db->connect();
        $user = People::with('peopleContacts')->getById(1);
        $this->assertEquals('testuser1', $user->username);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $user->peopleContacts);
        $this->assertEquals(2, $user['peopleContacts']->count());
    }

    public function testGetRelationshipsWith2()
    {
        $this->db->connect();
        $users = People::with('peopleContacts')->getBy(['username' => 'testuser1']);
        $this->assertEquals('testuser1', $users[0]->username);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $users[0]->peopleContacts);
        $this->assertEquals(2, $users[0]['peopleContacts']->count());
    }

    public function testGetRelationshipsWith3()
    {
        $this->db->connect();
        $info = PeopleInfo::with('people')->getBy(['metadata' => 'Some People Meta Data']);
        $this->assertEquals('Some People Meta Data', $info[0]->metadata);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $info[0]->people);
    }

    public function testGetRelationshipsWith4()
    {
        $this->db->connect();
        $users = People::with(['peopleInfo', 'peopleContacts'])->getBy(['username' => 'testuser1']);
        $this->assertEquals('testuser1', $users[0]->username);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $users[0]->peopleInfo);
        $this->assertEquals('Some People Notes', $users[0]->peopleInfo->notes);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $users[0]->peopleContacts);
        $this->assertEquals(2, $users[0]['peopleContacts']->count());
    }

    public function testGetRelationshipsWith5()
    {
        $this->db->connect();
        $users = People::with([
            'peopleInfo'     => ['order' => 'people_id ASC'],
            'peopleContacts' => ['order' => 'people_id ASC']
        ])->getBy(['username' => 'testuser1']);
        $this->assertEquals('testuser1', $users[0]->username);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $users[0]->peopleInfo);
        $this->assertEquals('Some People Notes', $users[0]->peopleInfo->notes);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $users[0]->peopleContacts);
        $this->assertEquals(2, $users[0]['peopleContacts']->count());
        $this->db->disconnect();
    }

    public function testGetHasManyRelationship()
    {
        $this->db->connect();
        $user         = People::findById(1);
        $relationship = new Relationships\HasMany($user, 'Pop\Db\Test\TestAsset\PeopleContact', 'people_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $relationship->getParent());
        $this->db->disconnect();
    }

    public function testGetHasOneRelationship()
    {
        $this->db->connect();
        $user         = People::findById(1);
        $relationship = new Relationships\HasOne($user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $relationship->getParent());
        $this->db->disconnect();
    }

    public function testGetHasOneOfRelationship()
    {
        $this->db->connect();
        $user         = People::findById(1);
        $relationship = new Relationships\HasOneOf($user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $relationship->getParent());
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $relationship->getChild());
        $this->db->disconnect();
    }

    public function testBelongsToGetParentWithChildRelationships()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\BelongsTo($info, 'Pop\Db\Test\TestAsset\People', 'people_id');
        $relationship->setChildRelationships(['peopleContacts']);
        $parent = $relationship->getParent();

        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $parent);
        $this->assertTrue($parent->hasRelationship('peopleContacts'));
        $this->db->disconnect();
    }

    public function testBelongsToEagerRelationshipsThrowsWhenForeignTableOrKeyMissing()
    {
        $this->db->connect();
        $this->expectException(Relationships\Exception::class);

        $info         = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);
        $relationship = new Relationships\BelongsTo($info, 'Pop\Db\Test\TestAsset\People', 'id');
        (new \ReflectionProperty($relationship, 'foreignTable'))->setValue($relationship, null);

        $relationship->getEagerRelationships([$info->people_id]);
        $this->db->disconnect();
    }

    public function testBelongsToEagerRelationshipsWithSelectOption()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\BelongsTo(
            $info, 'Pop\Db\Test\TestAsset\People', 'id',
            ['select' => ['id', 'username']]
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testBelongsToEagerRelationshipsWithLimitOffsetOrderOptions()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\BelongsTo(
            $info, 'Pop\Db\Test\TestAsset\People', 'id',
            ['limit' => 5, 'offset' => 0, 'order' => 'id DESC']
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testBelongsToEagerRelationshipsWithJoinOption()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\BelongsTo(
            $info, 'Pop\Db\Test\TestAsset\People', 'id',
            ['join' => ['type' => 'leftJoin', 'table' => 'people_contacts', 'columns' => ['people.id' => 'people_contacts.people_id']]]
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testBelongsToEagerRelationshipsWithDefaultJoinType()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        // No 'type' key - falls back to the default leftJoin() branch. Array-form
        // 'order' also exercises the branch that skips the comma-split parsing.
        $relationship = new Relationships\BelongsTo(
            $info, 'Pop\Db\Test\TestAsset\People', 'id',
            [
                'order' => ['id DESC'],
                'join'  => ['table' => 'people_contacts', 'columns' => ['people.id' => 'people_contacts.people_id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testHasOneGetChildWithColumnsOption()
    {
        $this->db->connect();
        $user  = People::findOne(['username' => 'testuser1']);
        $child = $user->peopleInfo(['columns' => ['metadata' => 'Some People Meta Data']]);

        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $child);
        $this->assertEquals('Some People Meta Data', $child->metadata);
        $this->db->disconnect();
    }

    public function testHasOneGetChildWithChildRelationships()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasOne($user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id');
        $relationship->setChildRelationships(['people']);
        $child = $relationship->getChild();

        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $child);
        $this->assertTrue($child->hasRelationship('people'));
        $this->db->disconnect();
    }

    public function testHasOneEagerRelationshipsWithOptions()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasOne(
            $user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id',
            [
                'select' => ['people_id', 'metadata', 'notes'],
                'limit'  => 5,
                'offset' => 0,
                'order'  => 'people_id DESC',
                'join'   => ['type' => 'leftJoin', 'table' => 'people', 'columns' => ['people_info.people_id' => 'people.id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$user->id]);

        $this->assertEquals('Some People Meta Data', $results[$user->id]->metadata);
        $this->db->disconnect();
    }

    public function testHasOneEagerRelationshipsWithDefaultJoinTypeAndArrayOrder()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasOne(
            $user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id',
            [
                'order' => ['people_id DESC'],
                'join'  => ['table' => 'people', 'columns' => ['people_info.people_id' => 'people.id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$user->id]);

        $this->assertEquals('Some People Meta Data', $results[$user->id]->metadata);
        $this->db->disconnect();
    }

    public function testHasOneOfGetChildWithChildRelationships()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\HasOneOf($info, 'Pop\Db\Test\TestAsset\People', 'people_id');
        $relationship->setChildRelationships(['peopleContacts']);
        $child = $relationship->getChild();

        $this->assertInstanceOf('Pop\Db\Test\TestAsset\People', $child);
        $this->assertTrue($child->hasRelationship('peopleContacts'));
        $this->db->disconnect();
    }

    public function testHasOneEagerRelationshipsThrowsWhenForeignTableOrKeyMissing()
    {
        $this->db->connect();
        $this->expectException(Relationships\Exception::class);

        $user         = People::findOne(['username' => 'testuser1']);
        $relationship = new Relationships\HasOne($user, 'Pop\Db\Test\TestAsset\PeopleInfo', 'people_id');
        (new \ReflectionProperty($relationship, 'foreignKey'))->setValue($relationship, null);

        $relationship->getEagerRelationships([$user->id]);
        $this->db->disconnect();
    }

    public function testHasOneOfEagerRelationshipsThrowsWhenForeignTableOrKeyMissing()
    {
        $this->db->connect();
        $this->expectException(Relationships\Exception::class);

        $info         = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);
        $relationship = new Relationships\HasOneOf($info, 'Pop\Db\Test\TestAsset\People', 'people_id');
        (new \ReflectionProperty($relationship, 'foreignKey'))->setValue($relationship, null);

        $relationship->getEagerRelationships([$info->people_id]);
        $this->db->disconnect();
    }

    public function testHasOneOfEagerRelationshipsWithOptions()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\HasOneOf(
            $info, 'Pop\Db\Test\TestAsset\People', 'people_id',
            [
                'select' => ['id', 'username'],
                'limit'  => 5,
                'offset' => 0,
                'order'  => 'id DESC',
                'join'   => ['type' => 'leftJoin', 'table' => 'people_contacts', 'columns' => ['people.id' => 'people_contacts.people_id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testHasOneOfEagerRelationshipsWithDefaultJoinType()
    {
        $this->db->connect();
        $info = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);

        $relationship = new Relationships\HasOneOf(
            $info, 'Pop\Db\Test\TestAsset\People', 'people_id',
            [
                'order' => ['id DESC'],
                'join'  => ['table' => 'people_contacts', 'columns' => ['people.id' => 'people_contacts.people_id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$info->people_id]);

        $this->assertEquals('testuser1', $results[$info->people_id]->username);
        $this->db->disconnect();
    }

    public function testHasManyGetChildrenWithColumnsOption()
    {
        $this->db->connect();
        $user     = People::findOne(['username' => 'testuser1']);
        $children = $user->peopleContacts(['columns' => ['email' => 'testuser1@test.com']]);

        $this->assertEquals(1, $children->count());
        $this->db->disconnect();
    }

    public function testHasManyEagerRelationshipsThrowsWhenForeignTableOrKeyMissing()
    {
        $this->db->connect();
        $this->expectException(Relationships\Exception::class);

        $user         = People::findOne(['username' => 'testuser1']);
        $relationship = new Relationships\HasMany($user, 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id');
        (new \ReflectionProperty($relationship, 'foreignTable'))->setValue($relationship, null);

        $relationship->getEagerRelationships([$user->id]);
        $this->db->disconnect();
    }

    public function testHasManyEagerRelationshipsWithColumnsOptionOnNonComposite()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasMany(
            $user, 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id',
            ['columns' => ['email' => 'testuser1@test.com']]
        );
        $results = $relationship->getEagerRelationships([$user->id]);

        $this->assertEquals(1, $results[$user->id]->count());
        $this->db->disconnect();
    }

    public function testHasManyEagerRelationshipsWithOptions()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasMany(
            $user, 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id',
            [
                'select' => ['people_id', 'email'],
                'limit'  => 5,
                'offset' => 0,
                'order'  => 'email DESC',
                'join'   => ['type' => 'leftJoin', 'table' => 'people', 'columns' => ['people_contacts.people_id' => 'people.id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$user->id]);

        $this->assertEquals(2, $results[$user->id]->count());
        $this->db->disconnect();
    }

    public function testHasManyEagerRelationshipsWithToArrayTrue()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasMany($user, 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id');
        $results      = $relationship->getEagerRelationships([$user->id], true);

        $this->assertIsArray($results[$user->id]);
        $this->assertEquals(2, count($results[$user->id]));
        $this->db->disconnect();
    }

    public function testHasManyEagerRelationshipsWithDefaultJoinTypeAndArrayOrder()
    {
        $this->db->connect();
        $user = People::findOne(['username' => 'testuser1']);

        $relationship = new Relationships\HasMany(
            $user, 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id',
            [
                'order' => ['email DESC'],
                'join'  => ['table' => 'people', 'columns' => ['people_contacts.people_id' => 'people.id']],
            ]
        );
        $results = $relationship->getEagerRelationships([$user->id]);

        $this->assertEquals(2, $results[$user->id]->count());
        $this->db->disconnect();
    }

    public function testMagicGetFallsBackToRelationshipMethod()
    {
        $this->db->connect();
        $user = People::findById(1);
        // Accessed as a property (no parens) - not yet in $relationships or rowGateway,
        // so __get() falls back to calling the same-named relationship method directly
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $user->peopleInfo);
        $this->db->disconnect();
    }

    public function testMagicIssetFallsBackToRelationshipMethod()
    {
        $this->db->connect();
        $user = People::findById(1);
        $this->assertTrue(isset($user->peopleInfo));
        $this->assertFalse(isset($user->notARealColumnOrMethod));
        $this->db->disconnect();
    }

    public function testBelongsToRelationship()
    {
        $this->db->connect();
        $info         = PeopleInfo::findOne(['metadata' => 'Some People Meta Data']);
        $relationship = new Relationships\BelongsTo($info, 'Pop\Db\Test\TestAsset\People', 'id');
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $relationship->getChild());
        $this->assertIsArray($relationship->getEagerRelationships([1]));
        $this->assertEquals('Pop\Db\Test\TestAsset\People', $relationship->getForeignTable());
        $this->assertEmpty($relationship->getOptions());
        $relationship->setChildRelationships(['TestChild']);
        $this->assertEquals(['TestChild'], $relationship->getChildRelationships());
        $this->db->disconnect();
    }

    public function testAddWithMergesSameNameChildren()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts.a');
        $user->addWith('peopleContacts.b');

        $reflection = new \ReflectionClass($user);

        $withProperty = $reflection->getProperty('with');
        $this->assertEquals(['peopleContacts'], $withProperty->getValue($user));

        $childrenProperty = $reflection->getProperty('withChildren');
        $this->assertEquals([['a', 'b']], $childrenProperty->getValue($user));
        $this->db->disconnect();
    }

    public function testAddWithDeduplicatesIdenticalChildren()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts.a');
        $user->addWith('peopleContacts.a');

        $reflection = new \ReflectionClass($user);
        $childrenProperty = $reflection->getProperty('withChildren');
        $this->assertEquals([['a']], $childrenProperty->getValue($user));
        $this->db->disconnect();
    }

    public function testAddWithKeepsDifferentNamesSeparate()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts');
        $user->addWith('peopleInfo');

        $reflection = new \ReflectionClass($user);
        $withProperty = $reflection->getProperty('with');
        $this->assertEquals(['peopleContacts', 'peopleInfo'], $withProperty->getValue($user));
        $this->db->disconnect();
    }

    public function testAddWithBareNameThenDottedNameMerges()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts');
        $user->addWith('peopleContacts.logs');

        $reflection = new \ReflectionClass($user);
        $withProperty = $reflection->getProperty('with');
        $this->assertEquals(['peopleContacts'], $withProperty->getValue($user));

        $childrenProperty = $reflection->getProperty('withChildren');
        $this->assertEquals([['logs']], $childrenProperty->getValue($user));
        $this->db->disconnect();
    }

    public function testAddWithUpdatesOptionsOnExistingName()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts');
        $user->addWith('peopleContacts', ['limit' => 5]);

        $reflection      = new \ReflectionClass($user);
        $withProperty    = $reflection->getProperty('with');
        $optionsProperty = $reflection->getProperty('withOptions');

        $this->assertEquals(['peopleContacts'], $withProperty->getValue($user));
        $this->assertEquals([['limit' => 5]], $optionsProperty->getValue($user));
        $this->db->disconnect();
    }

    public function testHasWith()
    {
        $this->db->connect();
        $user = new People();
        $this->assertFalse($user->hasWith('peopleContacts'));
        $user->addWith('peopleContacts');
        $this->assertTrue($user->hasWith('peopleContacts'));
        $this->db->disconnect();
    }

    public function testGetWiths()
    {
        $this->db->connect();
        $user = new People();
        $user->addWith('peopleContacts');
        $user->addWith('peopleInfo');
        $this->assertEquals(['peopleContacts', 'peopleInfo'], $user->getWiths());
        $this->db->disconnect();
    }

    /*
     * ---------------------------------------------------------------------------------------
     * RELATIONSHIP-GUARD-HANDOFF.md §3: lazy getters on an unloaded parent
     * ---------------------------------------------------------------------------------------
     */

    /**
     * Direct unit coverage of the guard predicate itself, independent of any DB round-trip.
     */
    public function testHasUsableParentKeyPredicate()
    {
        $relationship = new Relationships\HasMany(new People(), 'Pop\Db\Test\TestAsset\PeopleContacts', 'people_id');
        $method       = new \ReflectionMethod($relationship, 'hasUsableParentKey');

        // Single-key branch's degenerate form: array_values(getPrimaryValues()) collapsed
        $this->assertFalse($method->invoke($relationship, ['people_id' => []]));
        // Composite branch's degenerate form: every column read back null
        $this->assertFalse($method->invoke($relationship, ['a_id' => null, 'b_id' => null]));
        // A legitimate 0 or '' value must not be mistaken for "unloaded"
        $this->assertTrue($method->invoke($relationship, ['people_id' => 0]));
        $this->assertTrue($method->invoke($relationship, ['people_id' => '']));
        // One usable column alongside an unset one is still usable
        $this->assertTrue($method->invoke($relationship, ['a_id' => 5, 'b_id' => null]));
        $this->assertTrue($method->invoke($relationship, ['people_id' => 5]));
    }

    /**
     * §3.1 - single-key hasMany on a parent that was never loaded must return the same empty
     * result a real parent with no children would, without issuing a query and without the
     * self-inflicted deprecation the old `['people_id' => []]` shape triggered.
     */
    public function testHasManyOnUnloadedParentReturnsEmptyCollectionWithoutQuery()
    {
        $this->db->connect();

        $missing = People::findById(999999);

        $profiler = new Profiler();
        $this->db->setProfiler($profiler);

        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $contacts = $missing->peopleContacts();

        restore_error_handler();

        $this->assertFalse($deprecationTriggered);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $contacts);
        $this->assertEquals(0, $contacts->count());
        $this->assertEquals([], $contacts->toArray());
        $this->assertCount(0, $profiler->getSteps());

        $this->db->disconnect();
    }

    /**
     * §3.2 - single-key hasOne on an unloaded parent must return an empty Record (matching
     * findOne()'s no-match contract), not null, without issuing a query.
     */
    public function testHasOneOnUnloadedParentReturnsEmptyRecordWithoutQuery()
    {
        $this->db->connect();

        $missing = People::findById(999999);

        $profiler = new Profiler();
        $this->db->setProfiler($profiler);

        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $info = $missing->peopleInfo();

        restore_error_handler();

        $this->assertFalse($deprecationTriggered);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\PeopleInfo', $info);
        $this->assertFalse(isset($info->people_id));
        $this->assertCount(0, $profiler->getSteps());

        $this->db->disconnect();
    }

    /**
     * §3.5 - a loaded parent whose primary key value is legitimately 0 must NOT be treated as
     * "unloaded" - the guard checks value identity, not truthiness. Constructed locally rather
     * than via a real INSERT, since MySQL auto-increment columns don't reliably keep an
     * explicit 0.
     */
    public function testHasManyOnLoadedParentWithZeroPrimaryKeyStillQueries()
    {
        $this->db->connect();

        // people_contacts.people_id carries a real FK constraint to people.id, and MySQL
        // auto-increment columns don't reliably keep an explicit 0 on INSERT - disable the FK
        // check just for this seed so a people_id=0 contact can exist without a matching real
        // people row, which is all this test needs to prove the guard's 0-value predicate.
        $this->db->query('SET foreign_key_checks = 0');
        $zeroContact = new PeopleContacts(['people_id' => 0, 'email' => 'zero@test.com']);
        $zeroContact->save();
        $this->db->query('SET foreign_key_checks = 1');

        $zeroParent = new People();
        $zeroParent->setColumns(['id' => 0, 'username' => 'zero', 'password' => 'pw']);

        $contacts = $zeroParent->peopleContacts();

        $this->assertInstanceOf('Pop\Db\Record\Collection', $contacts);
        $this->assertEquals(1, $contacts->count());
        $this->assertEquals('zero@test.com', $contacts[0]->email);

        $this->db->disconnect();
    }

    /**
     * §3.6 - a real, loaded parent with no children must still query normally and get back an
     * empty collection - the guard must trigger on an unloaded PARENT, not on empty results.
     */
    public function testHasManyOnLoadedParentWithNoChildrenReturnsEmptyCollection()
    {
        $this->db->connect();

        $lonelyUser = new People(['username' => 'lonelyuser', 'password' => 'password1']);
        $lonelyUser->save();

        $contacts = $lonelyUser->peopleContacts();

        $this->assertInstanceOf('Pop\Db\Record\Collection', $contacts);
        $this->assertEquals(0, $contacts->count());

        $this->db->disconnect();
    }

    /**
     * §3.7 - the eager path is untouched: Record::hasMany() returns the relationship object
     * early when $eager is true, without ever reaching getChildren(), regardless of whether
     * the parent is loaded.
     */
    public function testHasManyEagerPathUntouchedOnUnloadedParent()
    {
        $this->db->connect();

        $missing      = People::findById(999999);
        $relationship = $missing->peopleContacts(null, true);

        $this->assertInstanceOf('Pop\Db\Record\Relationships\HasMany', $relationship);

        $this->db->disconnect();
    }

    /**
     * §3.8 - Record::hasMany() collapses a 1-row result to that single record when latest()/
     * oldest() is active. With the guard, an unloaded parent's child count is 0, not 1, so the
     * collapse must not fire - the empty Collection itself must come back.
     */
    public function testHasManyLatestOnUnloadedParentReturnsEmptyCollectionNotFirstElement()
    {
        $this->db->connect();

        $missing = People::findById(999999);
        $missing->latest();
        $contacts = $missing->peopleContacts();

        $this->assertInstanceOf('Pop\Db\Record\Collection', $contacts);
        $this->assertEquals(0, $contacts->count());

        $this->db->disconnect();
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