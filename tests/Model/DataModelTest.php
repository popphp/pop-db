<?php

namespace Pop\Db\Test\Model;

use Pop\Db\Db;
use Pop\Db\Record;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Pop\Db\Test\TestAsset\Model\Ghost;
use Pop\Db\Test\TestAsset\Model\User;
use Pop\Db\Test\TestAsset\Model\UserWithDefaultSelect;
use Pop\Db\Test\TestAsset\Model\UserWithJoin;
use Pop\Db\Test\TestAsset\Table\Users;

class DataModelTest extends TestCase
{

    /**
     * The AbstractDataModel::filter() string syntax (e.g. 'username LIKE testuser1%') is
     * converted internally to the legacy shorthand column format, which triggers pop-db's
     * E_USER_DEPRECATED notice. That's expected, real behavior of the documented filter()
     * API, not a bug in the model - assert it explicitly via expectUserDeprecationMessage()
     * rather than letting it show up as unasserted noise, and mark it #[IgnoreDeprecations]
     * so this known, asserted trigger doesn't get reported in the test run's summary.
     */
    protected const LEGACY_FILTER_DEPRECATION = "Deprecated: The shorthand column format used " .
        "for [username%] is deprecated and will be removed in pop-db v8. Use the structured " .
        "format instead, e.g. ['column' => ['>=', value]]. See README.md for the new syntax.";

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

    #[IgnoreDeprecations('shorthand column format')]
    public function testCountAndFilters1()
    {
        $this->expectUserDeprecationMessage(self::LEGACY_FILTER_DEPRECATION);

        $userModel = new User();
        $count     = $userModel->filter('username LIKE testuser1%', ['id', 'username'])->count();
        $users     = $userModel->getAll()->toArray();

        $this->assertEquals(1, $count);
        $this->assertEquals('testuser1', $users[0]['username']);
        $this->assertFalse(isset($users[0]['email']));
        $this->assertEquals(1, $users[0]['id']);

        Record::db()->disconnect();
    }

    #[IgnoreDeprecations('shorthand column format')]
    public function testCountAndFilters2()
    {
        $this->expectUserDeprecationMessage(self::LEGACY_FILTER_DEPRECATION);

        $users = User::filterBy('username LIKE testuser1%', ['id', 'username'])->getAll()->toArray();

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
