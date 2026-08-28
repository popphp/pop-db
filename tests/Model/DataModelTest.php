<?php

namespace Pop\Db\Test\Model;

use Pop\Db\Db;
use Pop\Db\Record;
use PHPUnit\Framework\TestCase;
use Pop\Db\Test\TestAsset\Model\Ghost;
use Pop\Db\Test\TestAsset\Model\User;
use Pop\Db\Test\TestAsset\Model\UserWithDefaultSelect;
use Pop\Db\Test\TestAsset\Model\UserWithJoin;
use Pop\Db\Test\TestAsset\Table\Users;

class DataModelTest extends TestCase
{

    public function setUp(): void
    {
        chmod(__DIR__ . '/../tmp', 0777);
        touch(__DIR__ . '/../tmp/datamodel.sqlite');
        chmod(__DIR__ . '/../tmp/datamodel.sqlite', 0777);

        $db = Db::sqliteConnect([
            'database' => __DIR__ . '/../tmp/datamodel.sqlite'
        ]);

        Record::setDb($db);
        Users::setDb($db);

        if (!$db->hasTable('data_model_users')) {
            $schema = $db->createSchema();
            $schema->create('data_model_users')
                ->int('id')->increment()
                ->varchar('username', 255)
                ->varchar('email', 255)
                ->primary('id');

            $db->query($schema);
        }

        if (!$db->hasTable('data_model_user_meta')) {
            $schema = $db->createSchema();
            $schema->create('data_model_user_meta')
                ->int('user_id')
                ->varchar('note', 255);

            $db->query($schema);
        }
    }

    public function testRequirements()
    {
        $userModel = new User();
        $this->assertTrue($userModel->hasRequirements());
    }

    public function testCreateBad()
    {
        $userModel = new User();
        $results   = $userModel->create([
            'email' => 'testuser1@test.com'
        ]);

        $this->assertTrue(isset($results['errors']));
        $this->assertTrue(isset($results['errors']['username']));
        $this->assertEquals("The column 'username' is required.", $results['errors']['username']);

        Record::db()->disconnect();
    }

    public function testCreate()
    {
        $user = User::createNew([
            'username' => 'testuser1',
            'email'    => 'testuser1@test.com'
        ]);

        $this->assertEquals('testuser1', $user['username']);
        $this->assertEquals('testuser1@test.com', $user['email']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testDescribe()
    {
        $userModel = new User();
        $this->assertCount(3, $userModel->describe());
    }

    public function testGetAll()
    {
        $users = User::fetchAll();

        $this->assertEquals('testuser1', $users[0]['username']);
        $this->assertEquals('testuser1@test.com', $users[0]['email']);
        $this->assertEquals(1, $users[0]['id']);
        $this->assertEquals(1, (new User())->count());

        Record::db()->disconnect();
    }

    /**
     * AbstractDataModel::parseFilter() builds the structured operator-tuple format directly
     * (PARSEFILTER-HANDOFF.md §2), so a LIKE expression like 'username LIKE testuser1%' no
     * longer round-trips through the legacy shorthand column format and no longer triggers
     * pop-db's E_USER_DEPRECATED notice. Assert that explicitly rather than letting a
     * regression back to the legacy path show up only as unasserted deprecation noise.
     */
    public function testCountAndFilters1()
    {
        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $userModel = new User();
        $count     = $userModel->filter('username LIKE testuser1%', ['id', 'username'])->count();
        $users     = $userModel->getAll()->toArray();

        restore_error_handler();

        $this->assertFalse($deprecationTriggered);
        $this->assertEquals(1, $count);
        $this->assertEquals('testuser1', $users[0]['username']);
        $this->assertFalse(isset($users[0]['email']));
        $this->assertEquals(1, $users[0]['id']);

        Record::db()->disconnect();
    }

    public function testCountAndFilters2()
    {
        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $users = User::filterBy('username LIKE testuser1%', ['id', 'username'])->getAll()->toArray();

        restore_error_handler();

        $this->assertFalse($deprecationTriggered);
        $this->assertEquals('testuser1', $users[0]['username']);
        $this->assertFalse(isset($users[0]['email']));
        $this->assertEquals(1, $users[0]['id']);

        Record::db()->disconnect();
    }

    public function testCountAndFiltersClear()
    {
        $userModel = new User();
        $count     = $userModel->filter(null, null)->count();
        $users     = $userModel->getAll();

        $this->assertEquals(1, $count);
        $this->assertEquals('testuser1', $users[0]['username']);
        $this->assertEquals('testuser1@test.com', $users[0]['email']);
        $this->assertEquals(1, $users[0]['id']);

        Record::db()->disconnect();
    }

    public function testGetById()
    {
        $user = User::fetch(1);

        $this->assertEquals('testuser1', $user['username']);
        $this->assertEquals('testuser1@test.com', $user['email']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testCountUnsetsOffsetAndLimitCarriedOverFromGetAll()
    {
        $userModel = new User();
        $userModel->getAll(null, 5, 1);
        $count = $userModel->count();

        $this->assertEquals(1, $count);

        Record::db()->disconnect();
    }

    public function testCountWithForeignTablesJoin()
    {
        $userModel = new UserWithJoin();
        $count     = $userModel->count();

        $this->assertEquals(1, $count);

        Record::db()->disconnect();
    }

    public function testDescribeNative()
    {
        $userModel = new User();
        $columns   = $userModel->describe(true);

        $this->assertEquals(['data_model_users.id', 'data_model_users.username', 'data_model_users.email'], $columns);

        Record::db()->disconnect();
    }

    public function testDescribeNativeFull()
    {
        $userModel = new User();
        $info      = $userModel->describe(true, true);

        $this->assertEquals('data_model_users', $info['tableName']);
        $this->assertArrayHasKey('columns', $info);

        Record::db()->disconnect();
    }

    public function testDescribeWithSelectColumns()
    {
        $userModel = new User();
        $userModel->select(['username']);
        $columns = $userModel->describe();

        $this->assertEquals(['username'], $columns);

        Record::db()->disconnect();
    }

    public function testGetTableClassThrowsWhenNoMatchingTableClassExists()
    {
        $this->expectException('Pop\Db\Model\Exception');
        (new Ghost())->getTableClass();
    }

    public function testSelectRepeatedColumnKeepsItsOriginalKeyAndMergesOptions()
    {
        $userModel = new UserWithDefaultSelect();
        // 'username' is already in the preset $selectColumns, so this exercises the
        // branch that re-keys it by its existing position instead of appending it,
        // and passing non-empty $options exercises the options-merge branch
        $userModel->select(['username'], ['limit' => 5]);

        $this->assertEquals(['username'], array_values($userModel->describe()));
    }

    public function testSelectWithNoColumnsRevertsToOriginalSelectColumns()
    {
        $userModel = new UserWithDefaultSelect();
        $userModel->select(['username']);
        // Calling select() with nothing reverts back to what selectColumns was
        // before the first select() call - the preset ['id', 'username']
        $userModel->select();

        $this->assertEquals(['id', 'username'], $userModel->describe());
    }

    public function testGetByIdWithFilters()
    {
        $userModel = new User();
        $userModel->filter('username = testuser1');
        $user = $userModel->getById(1);

        $this->assertEquals('testuser1', $user['username']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testGetOneWithFilters()
    {
        $userModel = new User();
        $userModel->filter('username = testuser1');
        $user = $userModel->getOne(['id' => 1]);

        $this->assertEquals('testuser1', $user['username']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testGetAllWithForeignTablesJoin()
    {
        Record::db()->query(
            "INSERT INTO data_model_user_meta (user_id, note) VALUES (1, 'first user note')"
        );

        $userModel = new UserWithJoin();
        $users     = $userModel->getAll();

        $this->assertEquals('testuser1', $users[0]['username']);

        Record::db()->disconnect();
    }

    public function testGetByIdWithForeignTablesJoin()
    {
        $userModel = new UserWithJoin();
        $user      = $userModel->getById(1);

        $this->assertEquals('testuser1', $user['username']);

        Record::db()->disconnect();
    }

    public function testGetOneWithForeignTablesJoin()
    {
        $userModel = new UserWithJoin();
        $user      = $userModel->getOne(['id' => 1]);

        $this->assertEquals('testuser1', $user['username']);

        Record::db()->disconnect();
    }

    public function testUpdate()
    {
        $userModel = new User();
        $user      = $userModel->update(1, [
            'username' => 'testuser2',
            'email'    => 'testuser2@test.com'
        ]);

        $this->assertEquals('testuser2', $user['username']);
        $this->assertEquals('testuser2@test.com', $user['email']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testCopy()
    {
        $userModel = new User();
        $user      = $userModel->copy(1);

        $this->assertEquals('testuser2', $user['username']);
        $this->assertEquals('testuser2@test.com', $user['email']);
        $this->assertEquals(2, $user['id']);

        Record::db()->disconnect();
    }

    public function testReplaceBad()
    {
        $userModel = new User();
        $results   = $userModel->replace(1, [
            'email' => 'testuser1@test.com'
        ]);

        $this->assertTrue(isset($results['errors']));
        $this->assertTrue(isset($results['errors']['username']));
        $this->assertEquals("The column 'username' is required.", $results['errors']['username']);

        Record::db()->disconnect();
    }

    public function testReplace()
    {
        $userModel = new User();
        $user      = $userModel->replace(1, [
            'username' => 'testuser3',
        ]);

        $this->assertEquals('testuser3', $user['username']);
        $this->assertNull($user['email']);
        $this->assertEquals(1, $user['id']);

        Record::db()->disconnect();
    }

    public function testRemove()
    {
        $userModel = new User();
        $users     = $userModel->remove([1]);
        $this->assertEquals(1, $users);

        Record::db()->disconnect();
    }

    public function testNoDelete()
    {
        $userModel = new User();
        $users     = $userModel->delete(1);
        $this->assertEquals(0, $users);

        Record::db()->disconnect();
    }

    public function testGetOffsetAndLimit()
    {
        $userModel    = new User();
        $offsetLimit1 = $userModel->getOffsetAndLimit(2, 10);
        $offsetLimit2 = $userModel->getOffsetAndLimit(null, 10);
        $this->assertEquals(10, $offsetLimit1['offset']);
        $this->assertEquals(10, $offsetLimit1['limit']);
        $this->assertEquals(null, $offsetLimit2['offset']);
        $this->assertEquals(10, $offsetLimit2['limit']);
    }

    public function testOrderBy()
    {
        $userModel = new User();
        $this->assertEquals('id DESC', $userModel->getOrderBy('-id'));
        $this->assertEquals('id ASC, username ASC', $userModel->getOrderBy('id,username'));
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.2 - two filter expressions on the same column must both
     * survive as a bounded range (via parseFilter()'s repeated-column 'AND' grouping) rather
     * than the second silently overwriting the first, end-to-end through a live query.
     */
    public function testGetAllWithRepeatedColumnFilterProducesBoundedRangeWithoutDeprecation()
    {
        $userModel = new User();
        $userModel->filter(['id >= 1', 'id <= 100']);

        $deprecationTriggered = false;
        set_error_handler(function ($errno) use (&$deprecationTriggered) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationTriggered = true;
            }
        }, E_USER_DEPRECATED);

        $users = $userModel->getAll()->toArray();

        restore_error_handler();

        $this->assertFalse($deprecationTriggered);
        $this->assertCount(1, $users);

        Record::db()->disconnect();
    }

    /**
     * PARSEFILTER-HANDOFF.md §3.5/§4.1 - a malformed filter expression surfaces the parser's
     * own exception rather than silently falling back to the legacy (still-deprecated) path.
     */
    public function testGetAllWithMalformedFilterThrowsParserException()
    {
        $userModel = new User();
        $userModel->filter('username <> testuser2');

        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $userModel->getAll();
    }

    public function testDropTable()
    {
        $db     = Record::db();
        $schema = $db->createSchema();
        $schema->drop('data_model_users');

        $this->assertTrue($db->hasTable('data_model_users'));
        $db->query($schema);
        $this->assertFalse($db->hasTable('data_model_users'));

        $db->disconnect();

        unlink(__DIR__ . '/../tmp/datamodel.sqlite');
    }

}
