<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Exception;
use Pop\Db\Record;
use Pop\Db\Test\TestAsset\MockData;
use Pop\Db\Test\TestAsset\Users;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

class RecordTest extends TestCase
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

        $schema->dropIfExists('users');
        $schema->execute();

        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->int('logins', 16)->defaultIs(0)
            ->primary('id');


        $schema->execute();

        \Pop\Db\Test\TestAsset\Users::setDb($this->db);
        \Pop\Db\Test\TestAsset\GuardedUsers::setDb($this->db);
        \Pop\Db\Test\TestAsset\FillableUsers::setDb($this->db);
    }

    public function testConstructor()
    {
        $user = new Users([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $user);
        $this->assertInstanceOf('Pop\Db\Record', $user);
        $this->db->disconnect();
    }

    public function testConstructorTable()
    {
        $user = new Users('users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testConstructorDb()
    {
        $user = new Users($this->db);
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Users::getDb());
        $this->db->disconnect();
    }

    public function testHasDb()
    {
        $user = new Users($this->db);
        $this->assertTrue(Users::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Users::db());
        $this->db->disconnect();
    }

    public function testSetRecordDb()
    {
        Record::setDb($this->db);
        $this->assertTrue(Record::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Record::db());
        $this->db->disconnect();
    }

    public function testSetDefaultDb()
    {
        Record::setDefaultDb($this->db);
        $this->assertTrue(Record::hasDb());
        $this->assertInstanceOf('Pop\Db\Adapter\Mysql', Record::db());
        $this->db->disconnect();
    }

    public function testGetSql()
    {
        $this->assertInstanceOf('Pop\Db\Sql', Users::getSql());
        $this->assertInstanceOf('Pop\Db\Sql', Users::sql());
        $this->db->disconnect();
    }

    public function testGetPredicateSet()
    {
        $this->assertInstanceOf('Pop\Db\Sql\PredicateSet', Users::predicate());
        $this->db->disconnect();
    }

    public function testTable()
    {
        $this->assertEquals('users', Users::table());
        $this->db->disconnect();
    }

    public function testSetTableWithClassName()
    {
        $user = new Users();
        $user->setTableFromClassName('Users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetTableWithUnderscore()
    {
        $user = new Users();
        $user->setTableFromClassName('MyApp_Users');
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetTable()
    {
        $user = new Users();
        $user->setTableFromClassName();
        $this->assertEquals('users', $user->getTable());
        $this->db->disconnect();
    }

    public function testSetPrefix()
    {
        $user = new Users();
        $user->setPrefix('prefix_');
        $this->assertEquals('prefix_', $user->getPrefix());
        $this->assertEquals('prefix_users', $user->getFullTable());
        $user->setPrefix('');
        $this->db->disconnect();
    }

    public function testSetPrimaryKeys()
    {
        $user = new Users();
        $user->setPrimaryKeys(['id']);
        $this->assertTrue((['id'] == $user->getPrimaryKeys()));
        $this->assertTrue(is_array($user->getPrimaryValues()));
        $this->db->disconnect();
    }

    public function testGetGateways()
    {
        $user = new Users();
        $this->assertInstanceOf('Pop\Db\Gateway\Row', $user->getRowGateway());
        $this->assertInstanceOf('Pop\Db\Gateway\Table', $user->getTableGateway());
        $this->assertTrue(is_array($user->toArray()));
        $this->assertEquals(0, $user->count());
        $this->assertEquals(0, count($user->rows()));
        $this->assertEquals(0, $user->countRows());
        $this->assertFalse($user->hasRows());

        $i = 0;
        foreach ($user as $u) {
            $i++;
        }
        $this->assertEquals(0, $i);
        $this->db->disconnect();
    }

    public function testIsFillableWithNeitherDeclaredAllowsEverything()
    {
        $user = new Users();
        $this->assertTrue($user->isFillable('username'));
        $this->assertTrue($user->isFillable('logins'));
        $this->assertTrue($user->isFillable('anything_at_all'));
        $this->db->disconnect();
    }

    public function testIsFillableWithGuardedDeclared()
    {
        $user = new \Pop\Db\Test\TestAsset\GuardedUsers();
        $this->assertTrue($user->isFillable('username'));
        $this->assertTrue($user->isFillable('email'));
        $this->assertFalse($user->isFillable('logins'));
        $this->db->disconnect();
    }

    public function testIsFillableWithFillableDeclared()
    {
        $user = new \Pop\Db\Test\TestAsset\FillableUsers();
        $this->assertTrue($user->isFillable('username'));
        $this->assertTrue($user->isFillable('email'));
        $this->assertFalse($user->isFillable('password'));
        $this->assertFalse($user->isFillable('logins'));
        $this->db->disconnect();
    }

    public function testFillWithGuardedDropsGuardedColumn()
    {
        $user = new \Pop\Db\Test\TestAsset\GuardedUsers();
        $user->fill([
            'username' => 'testuser1',
            'email'    => 'testuser1@test.com',
            'logins'   => 999
        ]);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertArrayNotHasKey('logins', $ary);
        $this->db->disconnect();
    }

    public function testFillWithFillableOnlyKeepsListedColumns()
    {
        $user = new \Pop\Db\Test\TestAsset\FillableUsers();
        $user->fill([
            'username' => 'testuser1',
            'password' => 'shouldnotbeset',
            'email'    => 'testuser1@test.com'
        ]);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('testuser1@test.com', $ary['email']);
        $this->assertArrayNotHasKey('password', $ary);
        $this->db->disconnect();
    }

    public function testFillWithNeitherDeclaredKeepsEverything()
    {
        $user = new Users();
        $user->fill([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('password1', $ary['password']);
        $this->assertEquals('testuser1@test.com', $ary['email']);
        $this->db->disconnect();
    }

    public function testGuardedColumnSurvivesRowHydration()
    {
        // Insert directly via the underlying Users class (not mass-assignment - this
        // simulates a column value that legitimately exists in the database already).
        $user = new Users([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com',
            'logins'   => 42
        ]);
        $user->save();

        // Fetch it back through the GuardedUsers class, which guards 'logins'.
        $found = \Pop\Db\Test\TestAsset\GuardedUsers::findById($user->id);

        $this->assertEquals(42, $found->logins);
        $this->db->disconnect();
    }

    public function testConstructorWithGuardedFiltersGuardedColumn()
    {
        // The primary, user-facing use case: untrusted input straight into the constructor.
        $user = new \Pop\Db\Test\TestAsset\GuardedUsers([
            'username' => 'testuser1',
            'email'    => 'testuser1@test.com',
            'logins'   => 999
        ]);

        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('testuser1@test.com', $ary['email']);
        $this->assertArrayNotHasKey('logins', $ary);
        $this->db->disconnect();
    }

    public function testConstructorWithFillableFiltersUnlistedColumns()
    {
        $user = new \Pop\Db\Test\TestAsset\FillableUsers([
            'username' => 'testuser1',
            'password' => 'shouldnotbeset',
            'email'    => 'testuser1@test.com',
            'logins'   => 999
        ]);

        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('testuser1@test.com', $ary['email']);
        $this->assertArrayNotHasKey('password', $ary);
        $this->assertArrayNotHasKey('logins', $ary);
        $this->db->disconnect();
    }

    public function testConstructorWithGuardedFiltersAndSavesFilteredRow()
    {
        $user = new \Pop\Db\Test\TestAsset\GuardedUsers([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com',
            'logins'   => 999
        ]);
        $user->save();

        // The guarded column never made it into the INSERT, so the column default applies.
        $found = Users::findById($user->id);
        $this->assertEquals('testuser1', $found->username);
        $this->assertEquals(0, $found->logins);
        $this->db->disconnect();
    }

    public function testReplicatePreservesGuardedColumn()
    {
        $user = new Users([
            'username' => 'testuser17',
            'password' => 'password17',
            'email'    => 'testuser17@test.com',
            'logins'   => 42
        ]);
        $user->save();

        // Fetch it through the guarded class, then replicate it. The replicated record is
        // built from the record's own trusted data, so the guarded column must survive.
        $found   = \Pop\Db\Test\TestAsset\GuardedUsers::findById($user->id);
        $newUser = $found->copy(['password' => '123456']);

        $this->assertEquals('testuser17', $newUser->username);
        $this->assertEquals('123456', $newUser->password);
        $this->assertEquals(42, $newUser->logins);

        // And it was actually persisted with the guarded value, not the column default.
        $reloaded = Users::findById($newUser->id);
        $this->assertNotEquals($user->id, $newUser->id);
        $this->assertEquals(42, $reloaded->logins);

        $users = Users::findBy(['username' => 'testuser17']);
        $this->assertEquals(2, $users->count());
        $this->db->disconnect();
    }

    public function testFindOneOrCreateWithGuardedCreatesExactlyOneRow()
    {
        $criteria = [
            'username' => 'testuser18',
            'password' => 'password18',
            'email'    => 'testuser18@test.com',
            'logins'   => 7
        ];

        $first  = \Pop\Db\Test\TestAsset\GuardedUsers::findOneOrCreate($criteria);
        $second = \Pop\Db\Test\TestAsset\GuardedUsers::findOneOrCreate($criteria);

        // The created row must match the criteria it was created from, guarded columns
        // included - otherwise every subsequent call misses it and creates a duplicate.
        $users = Users::findBy(['username' => 'testuser18']);
        $this->assertEquals(1, $users->count());
        $this->assertEquals(7, $users[0]->logins);

        $this->assertEquals(7, $first->logins);
        $this->assertEquals($first->id, $second->id);
        $this->db->disconnect();
    }

    public function testFindByOrCreateWithGuardedCreatesExactlyOneRow()
    {
        $criteria = [
            'username' => 'testuser19',
            'password' => 'password19',
            'email'    => 'testuser19@test.com',
            'logins'   => 9
        ];

        \Pop\Db\Test\TestAsset\GuardedUsers::findByOrCreate($criteria);
        \Pop\Db\Test\TestAsset\GuardedUsers::findByOrCreate($criteria);

        $users = Users::findBy(['username' => 'testuser19']);
        $this->assertEquals(1, $users->count());
        $this->assertEquals(9, $users[0]->logins);
        $this->db->disconnect();
    }

    public function testFillOnFetchedRecordThenSaveUpdatesExistingRow()
    {
        // The README-documented fill() pattern: fetch, fill from untrusted input, save.
        $user = new Users([
            'username' => 'testuser20',
            'password' => 'password20',
            'email'    => 'testuser20@test.com',
            'logins'   => 5
        ]);
        $user->save();

        $found = \Pop\Db\Test\TestAsset\GuardedUsers::findById($user->id);
        $found->fill([
            'email'  => 'updated20@test.com',
            'logins' => 999
        ]);
        $found->save();

        $reloaded = Users::findById($user->id);
        $this->assertEquals('testuser20', $reloaded->username);
        $this->assertEquals('updated20@test.com', $reloaded->email);
        $this->assertEquals(5, $reloaded->logins);
        $this->db->disconnect();
    }

    public function testHooksFireInOrderOnInsert()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser1',
            'password' => 'password1',
            'email'    => 'hookuser1@test.com'
        ]);
        $user->save();

        $this->assertEquals(
            ['beforeSave', 'beforeInsert', 'afterInsert', 'afterSave'],
            $user->hookLog
        );
        $this->db->disconnect();
    }

    public function testHooksFireInOrderOnUpdate()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser2',
            'password' => 'password1',
            'email'    => 'hookuser2@test.com'
        ]);
        $user->save();
        $user->hookLog = [];

        $user->username = 'hookuser2updated';
        $user->save();

        $this->assertEquals(
            ['beforeSave', 'beforeUpdate', 'afterUpdate', 'afterSave'],
            $user->hookLog
        );
        $this->db->disconnect();
    }

    public function testAfterDeleteCanReadRecordDataBeforeReset()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser3',
            'password' => 'password1',
            'email'    => 'hookuser3@test.com'
        ]);
        $user->save();
        $savedId = $user->id;
        $user->hookLog = [];

        $user->delete();

        $this->assertEquals(['beforeDelete', 'afterDelete:id=' . $savedId], $user->hookLog);
        $this->db->disconnect();
    }

    public function testAfterDeleteThrowLeavesRecordConsistentlyDeleted()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser6',
            'password' => 'password1',
            'email'    => 'hookuser6@test.com'
        ]);
        $user->save();
        $user->throwInAfterDelete = true;

        try {
            $user->delete();
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            // expected
        }

        // The DELETE already ran (and committed, absent an active transaction) before
        // afterDelete() threw - the in-memory record must consistently read as deleted
        // rather than partially reverting to look like the delete never happened.
        $this->assertTrue(empty($user->toArray()));

        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser6']);
        $this->assertTrue(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testBeforeInsertThrowAbortsSaveAndPropagates()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $this->expectException('Pop\Db\Record\Exception');

        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser4',
            'password' => 'password1',
            'email'    => 'hookuser4@test.com'
        ]);
        $user->throwInBeforeInsert = true;

        try {
            $user->save();
        } finally {
            $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser4']);
            $this->assertTrue(empty($found->toArray()));
            $this->db->disconnect();
        }
    }

    public function testBeforeDeleteThrowAbortsDelete()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser5',
            'password' => 'password1',
            'email'    => 'hookuser5@test.com'
        ]);
        $user->save();
        $user->throwInBeforeDelete = true;

        try {
            $user->delete();
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            // expected
        }

        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser5']);
        $this->assertFalse(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testBulkSaveDoesNotFireHooks()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers();
        $user->save([
            ['username' => 'bulkhook1', 'password' => 'p1', 'email' => 'b1@test.com'],
            ['username' => 'bulkhook2', 'password' => 'p2', 'email' => 'b2@test.com']
        ]);

        $this->assertEquals([], $user->hookLog);
        $this->db->disconnect();
    }

    public function testBulkDeleteDoesNotFireHooks()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'bulkdeletehook1',
            'password' => 'p1',
            'email'    => 'bd1@test.com'
        ]);
        $user->save();
        $savedId = $user->id;
        $user->hookLog = [];

        (new \Pop\Db\Test\TestAsset\HookedUsers())->delete(['id' => $savedId]);

        $this->assertEquals([], $user->hookLog);

        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['id' => $savedId]);
        $this->assertTrue(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testBeforeSaveThrowAbortsInsertAndPropagates()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $this->expectException('Pop\Db\Record\Exception');

        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser8',
            'password' => 'password1',
            'email'    => 'hookuser8@test.com'
        ]);
        $user->throwInBeforeSave = true;

        try {
            $user->save();
        } finally {
            $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser8']);
            $this->assertTrue(empty($found->toArray()));
            $this->db->disconnect();
        }
    }

    public function testBeforeUpdateThrowAbortsUpdateAndPropagates()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser9',
            'password' => 'password1',
            'email'    => 'hookuser9@test.com'
        ]);
        $user->save();
        $user->throwInBeforeUpdate = true;
        $user->username            = 'hookuser9updated';

        $caught = false;

        try {
            $user->save();
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertInstanceOf('Pop\Db\Record\Exception', $e);
        }

        $this->assertTrue($caught, 'Expected save() to throw.');

        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser9']);
        $this->assertFalse(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testAfterInsertThrowLeavesRecordPersisted()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'hookuser10',
            'password' => 'password1',
            'email'    => 'hookuser10@test.com'
        ]);
        $user->throwInAfterInsert = true;

        $caught = false;

        try {
            $user->save();
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertInstanceOf('Pop\Db\Record\Exception', $e);
        }

        $this->assertTrue($caught, 'Expected save() to throw.');

        // The INSERT already ran (and committed, absent an active transaction) before
        // afterInsert() threw - the row must be persisted in the database even though
        // the exception propagated to the caller.
        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'hookuser10']);
        $this->assertFalse(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testSaveRollsBackTransactionOnException()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = \Pop\Db\Test\TestAsset\HookedUsers::start([
            'username' => 'txrollback1',
            'password' => 'password1',
            'email'    => 'txrollback1@test.com'
        ]);
        $user->throwInAfterInsert = true;

        $caught = false;

        try {
            $user->save();
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertInstanceOf('Pop\Db\Record\Exception', $e);
        }

        $this->assertTrue($caught, 'Expected save() to throw.');
        $this->assertFalse($user->isTransaction());

        // The INSERT ran inside an active transaction, so the thrown afterInsert() hook's
        // rollbackTransaction() call must have genuinely undone it in the database - unlike
        // the no-transaction case (testAfterInsertThrowLeavesRecordPersisted), where the
        // same INSERT stays committed because there was no transaction to roll back.
        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'txrollback1']);
        $this->assertTrue(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testDeleteRollsBackTransactionOnException()
    {
        \Pop\Db\Test\TestAsset\HookedUsers::setDb($this->db);
        $user = new \Pop\Db\Test\TestAsset\HookedUsers([
            'username' => 'txrollback2',
            'password' => 'password2',
            'email'    => 'txrollback2@test.com'
        ]);
        $user->save();

        $user->startTransaction();
        $user->throwInAfterDelete = true;

        $caught = false;

        try {
            $user->delete();
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertInstanceOf('Pop\Db\Record\Exception', $e);
        }

        $this->assertTrue($caught, 'Expected delete() to throw.');
        $this->assertFalse($user->isTransaction());

        // The DELETE ran inside an active transaction, so the thrown afterDelete() hook's
        // rollbackTransaction() call must have genuinely undone it - the row must still exist,
        // unlike the no-transaction afterDelete-throw case covered elsewhere in this file.
        $found = \Pop\Db\Test\TestAsset\HookedUsers::findOne(['username' => 'txrollback2']);
        $this->assertFalse(empty($found->toArray()));
        $this->db->disconnect();
    }

    public function testUndeclaredHooksAreNoOpsByDefault()
    {
        $user = new Users([
            'username' => 'plainuser1',
            'password' => 'password1',
            'email'    => 'plainuser1@test.com'
        ]);
        $user->save();
        $userId = $user->id;
        $user->username = 'plainuser1updated';
        $user->save();

        $updated = Users::findById($userId);
        $this->assertEquals('plainuser1updated', $updated->username);
        $this->assertEquals(1, Users::findAll(['id' => $userId])->count());

        $user->delete();
        $this->assertTrue(empty($user->toArray()));
        $this->db->disconnect();
    }

    public function testSetColumnsArrayAccess()
    {
        $data = new MockData([
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $user = new Users();
        $user->setColumns($data);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('password1', $ary['password']);
        $this->db->disconnect();
    }

    public function testSetColumnsRecord()
    {
        $data = new Users([
            'username' => 'testuser1',
            'password' => 'password1'
        ]);
        $user = new Users();
        $user->setColumns($data);
        $ary = $user->toArray();
        $this->assertEquals('testuser1', $ary['username']);
        $this->assertEquals('password1', $ary['password']);
        $this->db->disconnect();
    }

    public function testSetColumnsException()
    {
        $this->expectException('Pop\Db\Record\Exception');
        $user = new Users();
        $user->setColumns('bad');
    }

    public function testSetRows()
    {
        $user = new Users();
        $user->setRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ]);
        $this->assertEquals(2, $user->countRows());
        $this->db->disconnect();
    }

    public function testProcessRows()
    {
        $user = new Users();
        $rows = $user->processRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ]);
        $this->assertEquals(2, count($rows));
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $rows[0]);
        $this->assertInstanceOf('Pop\Db\Test\TestAsset\Users', $rows[1]);
        $this->db->disconnect();
    }

    public function testProcessRowsAsArray()
    {
        $user = new Users();
        $rows = $user->processRows([
            [
                'username' => 'testuser1',
                'password' => 'password1'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2'
            ]
        ], true);
        $this->assertEquals(2, count($rows));
        $this->assertIsArray($rows[0]);
        $this->assertIsArray($rows[1]);
        $this->db->disconnect();
    }

    public function testSettersAndGetters()
    {
        $user = new Users();
        $user->username   = 'testuser1';
        $user['password'] = 'password1';
        $this->assertTrue(isset($user->username));
        $this->assertTrue(isset($user['password']));
        $this->assertEquals('testuser1', $user->username);
        $this->assertEquals('password1', $user['password']);
        unset($user->username);
        unset($user['password']);
        $this->assertFalse(isset($user->username));
        $this->assertFalse(isset($user['password']));
        $this->db->disconnect();
    }

    public function testFindOne()
    {
        $user = new Users([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);

        $user->save();
        $uId = $user->id;

        $user2 = Users::findOne(['id' => $uId]);
        $this->assertTrue(isset($user2->id));
        $this->assertEquals('testuser1', $user2->username);
        $this->assertEquals('password1', $user2->password);
        $this->assertEquals('testuser1@test.com', $user2->email);
        $this->db->disconnect();
    }

    public function testFindOneOrCreate()
    {
        $user = Users::findOne([
            'username' => 'testuser2',
            'password' => 'password2',
            'email'    => 'testuser2@test.com'
        ]);

        $this->assertFalse(isset($user->id));

        $user = Users::findOneOrCreate([
            'username' => 'testuser2',
            'password' => 'password2',
            'email'    => 'testuser2@test.com'
        ]);

        $user = Users::findOne([
            'username' => 'testuser2',
            'password' => 'password2',
            'email'    => 'testuser2@test.com'
        ]);

        $this->assertTrue(isset($user->id));
        $this->assertEquals('testuser2', $user->username);
        $this->assertEquals('password2', $user->password);
        $this->assertEquals('testuser2@test.com', $user->email);

        $user = Users::findOneOrCreate([
            'username' => 'testuser2',
            'password' => 'password2',
            'email'    => 'testuser2@test.com'
        ]);

        $this->assertTrue(isset($user->id));
        $this->assertEquals('testuser2', $user->username);
        $this->assertEquals('password2', $user->password);
        $this->assertEquals('testuser2@test.com', $user->email);
        $this->db->disconnect();
    }

    public function testFindOneOrCreateWithPredicateSetExtractsValuesForNewRecord()
    {
        $predicateSet = Users::predicate()
            ->equalTo('username', 'predicatecreate1')
            ->equalTo('password', 'predicatepassword1')
            ->equalTo('email', 'predicatecreate1@test.com');

        $user = Users::findOne($predicateSet);
        $this->assertFalse(isset($user->id));

        // findOneOrCreate() with no matching row must fall through the
        // PredicateSet::extractValues() branch (columns instanceof PredicateSet) to build the
        // new record from the search criteria, exactly as the plain-array form does.
        $user = Users::findOneOrCreate($predicateSet);

        $this->assertTrue(isset($user->id));
        $this->assertEquals('predicatecreate1', $user->username);
        $this->assertEquals('predicatepassword1', $user->password);
        $this->assertEquals('predicatecreate1@test.com', $user->email);
        $this->db->disconnect();
    }

    public function testFindLatest()
    {
        $user = new Users([
            'username' => 'testuser3',
            'password' => 'password3',
            'email'    => 'testuser3@test.com'
        ]);

        $user->save();

        $user = Users::findLatest();
        $this->assertTrue(isset($user->id));
        $this->assertEquals('testuser3', $user->username);
        $this->assertEquals('password3', $user->password);
        $this->assertEquals('testuser3@test.com', $user->email);
        $this->db->disconnect();
    }

    public function testFindLatestWithOrder()
    {
        $user = new Users([
            'username' => 'testuser4',
            'password' => 'password4',
            'email'    => 'testuser4@test.com'
        ]);

        $user->save();

        $user = Users::findLatest(null, null, ['order' => 'id DESC']);
        $this->assertTrue(isset($user->id));
        $this->assertEquals('testuser4', $user->username);
        $this->assertEquals('password4', $user->password);
        $this->assertEquals('testuser4@test.com', $user->email);
        $this->db->disconnect();
    }

    public function testFindBy()
    {
        $user = new Users([
            'username' => 'testuser5',
            'password' => 'password5',
            'email'    => 'testuser5@test.com'
        ]);

        $user->save();

        $user = Users::findBy(['username' => 'testuser5']);
        $this->assertEquals(1, $user->count());
        $this->assertEquals('testuser5', $user[0]->username);
        $this->assertEquals('password5', $user[0]->password);
        $this->assertEquals('testuser5@test.com', $user[0]->email);
        $this->db->disconnect();
    }

    public function testFindByNewShorthandSyntax()
    {
        $user = new Users([
            'username' => 'newsyntaxuser',
            'password' => 'password',
            'email'    => 'newsyntax@test.com',
            'logins'   => 42
        ]);
        $user->save();

        $found = Users::findBy(['logins' => ['>=', 40]]);
        $this->assertGreaterThanOrEqual(1, $found->count());
        $this->assertEquals('newsyntaxuser', $found[0]->username);
        $this->db->disconnect();
    }

    public function testFindByOrGroupSyntax()
    {
        $user = new Users([
            'username' => 'orgroupuser',
            'password' => 'password',
            'email'    => 'orgroup@test.com',
            'logins'   => 0
        ]);
        $user->save();

        $found = Users::findBy([
            'username' => ['=', 'orgroupuser'],
            'OR' => [
                ['logins' => ['>=', 999]],
                ['email' => ['=', 'orgroup@test.com']],
            ],
        ]);
        $this->assertEquals(1, $found->count());
        $this->assertEquals('orgroupuser', $found[0]->username);
        $this->db->disconnect();
    }

    /**
     * End-to-end proof of the README's documented Record-layer subquery usage:
     * Users::findBy(['col' => ['IN', $subquery]]) executed against a live database.
     */
    public function testFindByWithSubqueryLiveExecution()
    {
        $this->db->query('DROP TABLE IF EXISTS `record_sub_orders`');
        $this->db->query(
            'CREATE TABLE `record_sub_orders` (`id` INT NOT NULL AUTO_INCREMENT, ' .
            '`user_id` INT NOT NULL, `total` INT NOT NULL, PRIMARY KEY (`id`))'
        );

        $userA = new Users([
            'username' => 'subqueryuser_a', 'password' => 'password', 'email' => 'sub_a@test.com'
        ]);
        $userA->save();

        $userB = new Users([
            'username' => 'subqueryuser_b', 'password' => 'password', 'email' => 'sub_b@test.com'
        ]);
        $userB->save();

        $userC = new Users([
            'username' => 'subqueryuser_c', 'password' => 'password', 'email' => 'sub_c@test.com'
        ]);
        $userC->save();

        // A has a qualifying order (>= 100); B has one that does NOT qualify; C has none at all.
        // So if the subquery's inner WHERE were silently dropped, B would wrongly appear.
        $this->db->query(
            'INSERT INTO `record_sub_orders` (`user_id`, `total`) VALUES ' .
            '(' . (int)$userA->id . ', 250), (' . (int)$userB->id . ', 25)'
        );

        $subquery = $this->db->createSql()->select('user_id')->from('record_sub_orders');
        $subquery->where->greaterThanOrEqualTo('total', 100);

        $found = Users::findBy(['id' => ['IN', $subquery]]);

        $this->assertEquals(1, $found->count());
        $this->assertEquals('subqueryuser_a', $found[0]->username);
        $this->assertEquals((int)$userA->id, (int)$found[0]->id);

        // The inverse form must exclude A and include B and C
        $notInSubquery = $this->db->createSql()->select('user_id')->from('record_sub_orders');
        $notInSubquery->where->greaterThanOrEqualTo('total', 100);

        $notFound  = Users::findBy(['id' => ['NOT IN', $notInSubquery]]);
        $usernames = array_column($notFound->toArray(), 'username');

        $this->assertContains('subqueryuser_b', $usernames);
        $this->assertContains('subqueryuser_c', $usernames);
        $this->assertNotContains('subqueryuser_a', $usernames);

        $this->db->query('DROP TABLE `record_sub_orders`');

        $userA->delete();
        $userB->delete();
        $userC->delete();

        $this->db->disconnect();
    }

    public function testFindByLegacySyntaxStillWorks()
    {
        $user = new Users([
            'username' => 'legacyuser',
            'password' => 'password',
            'email'    => 'legacy@test.com',
            'logins'   => 7
        ]);
        $user->save();

        $found = @Users::findBy(['logins>=' => 7, 'username' => 'legacyuser']);
        $this->assertEquals(1, $found->count());
        $this->assertEquals('legacyuser', $found[0]->username);
        $this->db->disconnect();
    }

    public function testFindIn()
    {

        $user1 = new Users([
            'username' => 'testuser1',
            'password' => 'password1',
            'email'    => 'testuser1@test.com'
        ]);
        $user1->save();

        $user2 = new Users([
            'username' => 'testuser2',
            'password' => 'password2',
            'email'    => 'testuser2@test.com'
        ]);
        $user2->save();

        $user3 = new Users([
            'username' => 'testuser3',
            'password' => 'password3',
            'email'    => 'testuser3@test.com'
        ]);
        $user3->save();

        $users = Users::findIn('id', [1, 2, 3]);
        $this->assertEquals(3, count($users));
        $this->assertTrue(isset($users[1]));
        $this->assertTrue(isset($users[2]));
        $this->assertTrue(isset($users[3]));
        $this->db->disconnect();
    }

    public function testFindByOrCreate()
    {
        $user = Users::findBy([
            'username' => 'testuser6',
            'password' => 'password6',
            'email'    => 'testuser6@test.com'
        ]);

        $this->assertEquals(0, $user->count());

        $user = Users::findByOrCreate([
            'username' => 'testuser6',
            'password' => 'password6',
            'email'    => 'testuser6@test.com'
        ]);

        $user = Users::findBy([
            'username' => 'testuser6',
            'password' => 'password6',
            'email'    => 'testuser6@test.com'
        ]);

        $this->assertEquals(1, $user->count());
        $this->assertEquals('testuser6', $user[0]->username);
        $this->assertEquals('password6', $user[0]->password);
        $this->assertEquals('testuser6@test.com', $user[0]->email);

        $user = Users::findByOrCreate([
            'username' => 'testuser6',
            'password' => 'password6',
            'email'    => 'testuser6@test.com'
        ]);

        $this->assertEquals(1, $user->count());
        $this->assertEquals('testuser6', $user[0]->username);
        $this->assertEquals('password6', $user[0]->password);
        $this->assertEquals('testuser6@test.com', $user[0]->email);
        $this->db->disconnect();
    }

    public function testFindByOrCreateWithPredicateSetExtractsValuesForNewRecord()
    {
        $predicateSet = Users::predicate()
            ->equalTo('username', 'predicatecreate2')
            ->equalTo('password', 'predicatepassword2')
            ->equalTo('email', 'predicatecreate2@test.com');

        $user = Users::findBy($predicateSet);
        $this->assertEquals(0, $user->count());

        // findByOrCreate() with no matching rows must fall through the
        // PredicateSet::extractValues() branch (columns instanceof PredicateSet) to build the
        // new record from the search criteria, exactly as the plain-array form does.
        $user = Users::findByOrCreate($predicateSet);

        $this->assertTrue(isset($user->id));
        $this->assertEquals('predicatecreate2', $user->username);
        $this->assertEquals('predicatepassword2', $user->password);
        $this->assertEquals('predicatecreate2@test.com', $user->email);
        $this->db->disconnect();
    }

    public function testFindAll()
    {
        $user = new Users([
            'username' => 'testuser7',
            'password' => 'password7',
            'email' => 'testuser7@test.com'
        ]);
        $user->save();

        $users1 = Users::findAll();
        $this->assertGreaterThan(0, $users1->count());

        $users2 = (new Users())->getAll();
        $this->assertGreaterThan(0, $users2->count());
        $this->db->disconnect();
    }

    public function testQuery1()
    {
        $sql = Users::sql();
        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => 'testuser8',
                'password' => 'password8',
                'email'    => 'testuser8@test.com'
            ]);

        Users::query($sql);

        $sql->reset();
        $sql->select()->from(Users::table())->where("username = 'testuser8'");

        $users = Users::query($sql);

        $this->assertTrue(isset($users[0]->id));
        $this->assertEquals('testuser8', $users[0]->username);
        $this->assertEquals('password8', $users[0]->password);
        $this->assertEquals('testuser8@test.com', $users[0]->email);
        $this->db->disconnect();
    }

    public function testQuery2()
    {
        $sql = Users::sql();
        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => 'testuser9',
                'password' => 'password9',
                'email'    => 'testuser9@test.com'
            ]);

        Users::query($sql);

        $sql->reset();

        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => 'testuser10',
                'password' => 'password10',
                'email'    => 'testuser10@test.com'
            ]);

        Users::query($sql);

        $sql->reset();
        $sql->select()->from(Users::table())->where("username LIKE 'testuser%'");

        $users = Users::query($sql);

        $this->assertGreaterThan(1, $users->count());
        $this->db->disconnect();
    }

    public function testExecute1()
    {
        $sql = Users::sql();
        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => '?',
                'password' => '?',
                'email'    => '?'
            ]);

        $params = [
            'username' => 'testuser11',
            'password' => 'password11',
            'email'    => 'testuser11@test.com'
        ];

        Users::execute($sql, $params);

        $sql->reset();
        $sql->select()->from(Users::table())->where("username = ?");

        $users = Users::execute($sql, ['username' => 'testuser11']);

        $this->assertTrue(isset($users[0]->id));
        $this->assertEquals('testuser11', $users[0]->username);
        $this->assertEquals('password11', $users[0]->password);
        $this->assertEquals('testuser11@test.com', $users[0]->email);
        $this->db->disconnect();
    }

    public function testExecute2()
    {
        $sql = Users::sql();
        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => '?',
                'password' => '?',
                'email'    => '?',
            ]);

        $params = [
            'username' => 'testuser12',
            'password' => 'password12',
            'email'    => 'testuser12@test.com'
        ];

        Users::execute($sql, $params);

        $sql->reset();

        $sql->insert()
            ->into(Users::table())
            ->values([
                'username' => '?',
                'password' => '?',
                'email'    => '?',
            ]);

        $params = [
            'username' => 'testuser13',
            'password' => 'password13',
            'email'    => 'testuser13@test.com'
        ];

        Users::execute($sql, $params);

        $sql->reset();
        $sql->select()->from(Users::table())->where("username LIKE ?");

        $users = Users::execute($sql, ['username' => 'testuser%']);

        $this->assertGreaterThan(1, $users->count());
        $this->db->disconnect();
    }

    public function testIncrement()
    {
        $user = new Users([
            'username' => 'testuser14',
            'password' => 'password14',
            'email'    => 'testuser14@test.com',
            'logins'   => 1
        ]);

        $user->save();

        $uId = $user->id;

        $user->increment('logins');

        $user = Users::findById($uId);
        $this->assertEquals(2, $user->logins);

        $user->decrement('logins');

        $user = Users::findById($uId);
        $this->assertEquals(1, $user->logins);
        $this->db->disconnect();
    }

    public function testReplicate()
    {
        $user = new Users([
            'username' => 'testuser15',
            'password' => 'password15',
            'email'    => 'testuser15@test.com',
            'logins'   => 1
        ]);

        $user->save();

        $newUser = $user->copy(['password' => '123456']);
        $this->assertEquals('testuser15', $newUser->username);
        $this->assertEquals('123456', $newUser->password);
        $this->assertEquals('testuser15@test.com', $newUser->email);

        $users = Users::findBy(['username' => 'testuser15']);
        $this->assertEquals(2, $users->count());
        $this->db->disconnect();
    }

    public function testDirty()
    {
        $user = new Users([
            'username' => 'testuser16',
            'password' => 'password16',
            'email'    => 'testuser16@test.com',
            'logins'   => 1
        ]);

        $user->save();

        $uId = $user->id;

        $newUser = Users::findById($uId);
        $this->assertFalse($newUser->isDirty());
        $newUser->username = 'testuser16-rev';
        $this->assertTrue($newUser->isDirty());

        $dirty = $newUser->getDirty();
        $this->assertTrue(isset($dirty['old']));
        $this->assertTrue(isset($dirty['old']['username']));
        $this->assertTrue(isset($dirty['new']));
        $this->assertTrue(isset($dirty['new']['username']));
        $this->assertEquals('testuser16', $dirty['old']['username']);
        $this->assertEquals('testuser16-rev', $dirty['new']['username']);

        $newUser->resetDirty();
        $this->assertFalse($newUser->isDirty());

        $this->db->disconnect();
    }

    public function testUpdate()
    {
        $user = new Users([
            'username' => 'testuser17',
            'password' => 'password17',
            'email'    => 'testuser17@test.com',
            'logins'   => 1
        ]);

        $user->save();

        $uId = $user->id;
        $newUser1 = Users::findById($uId);
        $newUser1->username = 'testuser17-rev';
        $newUser1->save();

        $newUser2 = Users::findById($uId);
        $this->assertEquals('testuser17-rev', $newUser2->username);

        $this->db->disconnect();
    }

    public function testSave()
    {
        $user = new Users();
        $user->save([
            'username' => 'testuser18',
            'password' => 'password18',
            'email'    => 'testuser18@test.com',
            'logins'   => 1
        ]);

        $newUser = Users::findOne(['username' => 'testuser18']);
        $this->assertTrue(isset($newUser->id));
        $this->assertEquals('testuser18', $newUser->username);

        $this->db->disconnect();
    }

    #[IgnoreDeprecations('shorthand column format used for \[username%\]')]
    public function testSaveMultiple()
    {
        $this->expectUserDeprecationMessageMatches('/shorthand column format used for \[username%\]/');

        $user = new Users();
        $user->save([
            [
                'username' => 'testuser20',
                'password' => 'password20',
                'email'    => 'testuser20@test.com',
                'logins'   => 1
            ],
            [
                'username' => 'testuser21',
                'password' => 'password21',
                'email'    => 'testuser21@test.com',
                'logins'   => 1
            ]
        ]);

        $newUsers = Users::findBy(['username%' => 'testuser2']);
        $this->assertEquals(2, $newUsers->count());

        $this->db->disconnect();
    }

    public function testDelete()
    {
        $user = new Users([
            'username' => 'testuser19',
            'password' => 'password19',
            'email'    => 'testuser19@test.com',
            'logins'   => 1
        ]);
        $user->save();

        $uId = $user->id;

        $newUser1 = Users::findById($uId);
        $this->assertTrue(isset($newUser1->id));
        $newUser1->delete();

        $newUser2 = Users::findById($uId);
        $this->assertFalse(isset($newUser2->id));

        $this->db->disconnect();
    }

    #[IgnoreDeprecations('shorthand column format used for \[username%\]')]
    public function testDeleteMultiple()
    {
        $this->expectUserDeprecationMessageMatches('/shorthand column format used for \[username%\]/');

        $user = new Users();
        $user->save([
            [
                'username' => 'testuser22',
                'password' => 'password22',
                'email'    => 'testuser22@test.com',
                'logins'   => 1
            ],
            [
                'username' => 'testuser23',
                'password' => 'password23',
                'email'    => 'testuser23@test.com',
                'logins'   => 1
            ]
        ]);

        $newUsers = Users::findBy(['username%' => 'testuser2']);
        $this->assertEquals(2, $newUsers->count());

        $newUser = new Users();
        $newUser->delete(['username%' => 'testuser2']);

        $newUsers = Users::findBy(['username%' => 'testuser2']);
        $this->assertEquals(0, $newUsers->count());

        $this->db->disconnect();
    }

    public function testDeleteMultipleNewShorthandSyntax()
    {
        $user = new Users();
        $user->save([
            [
                'username' => 'testuser25',
                'password' => 'password25',
                'email'    => 'testuser25@test.com',
                'logins'   => 5
            ],
            [
                'username' => 'testuser26',
                'password' => 'password26',
                'email'    => 'testuser26@test.com',
                'logins'   => 50
            ]
        ]);

        $newUser = new Users();
        $newUser->delete(['logins' => ['>=', 40]]);

        $this->assertEquals(1, Users::findBy(['username' => 'testuser25'])->count());
        $this->assertEquals(0, Users::findBy(['username' => 'testuser26'])->count());

        Users::findBy(['username' => 'testuser25'])[0]->delete();

        $this->db->disconnect();
    }

    #[IgnoreDeprecations('shorthand column format used for \[username%\]')]
    public function testGetTotal()
    {
        $this->expectUserDeprecationMessageMatches('/shorthand column format used for \[username%\]/');

        $user = new Users();
        $user->save([
            [
                'username' => 'testuser24',
                'password' => 'password24',
                'email'    => 'testuser24@test.com',
                'logins'   => 1
            ],
            [
                'username' => 'testuser25',
                'password' => 'password25',
                'email'    => 'testuser25@test.com',
                'logins'   => 1
            ]
        ]);

        $this->assertEquals(2, Users::getTotal(['username%' => 'testuser2']));

        $this->db->disconnect();
    }

    public function testGetTableInfo()
    {
        $info = Users::getTableInfo();
        $this->assertIsArray($info);
        $this->assertEquals('users', $info['tableName']);

        $this->db->disconnect();
    }

    public function testTransaction()
    {
        $user = new Users([
            'username' => 'testuser262',
            'password' => 'password262',
            'email'    => 'testuser262@test.com',
            'logins'   => 1
        ]);
        $user->startTransaction();
        $user->save();


        $newUsers = Users::findWhereUsername('testuser262');
        $this->assertEquals(1, $newUsers->count());
        $this->assertEquals('testuser262', $newUsers[0]->username);

        $this->db->disconnect();
    }

    public function testTransactionRollback()
    {
        $user = new Users([
            'username' => 'testuser263',
            'password' => 'password263',
            'email'    => 'testuser263@test.com',
            'logins'   => 1
        ]);
        $user->startTransaction();
        $user->save(null, false);
        $user->rollbackTransaction();

        $newUsers = Users::findWhereUsername('testuser263');
        $this->assertEquals(0, $newUsers->count());

        $this->db->disconnect();
    }

    public function testGlobalTransaction()
    {
        Users::transaction(function(){
            $user = new Users([
                'username' => 'testuser26',
                'password' => 'password26',
                'email'    => 'testuser26@test.com',
                'logins'   => 1
            ]);
            $user->save();
        });


        $newUsers = Users::findWhereUsername('testuser26');
        $this->assertEquals(1, $newUsers->count());
        $this->assertEquals('testuser26', $newUsers[0]->username);

        $this->db->disconnect();
    }

    public function testGlobalTransactionRollback()
    {
        $this->expectException('Pop\Db\Exception');

        Users::transaction(function(){
            $user = new Users([
                'username' => 'testuser27',
                'password' => 'password27',
                'email'    => 'testuser26@test.com',
                'logins'   => 1
            ]);
            throw new Exception('Whoops!');
        });
    }

    public function testTransactionNesting()
    {
        $this->assertFalse($this->db->isTransaction());
        $this->assertEquals(0, $this->db->getTransactionDepth());
        $user = Users::start([
            'username' => 'testuser264',
            'password' => 'password27',
            'email'    => 'testuser26@test.com',
            'logins'   => 1
        ]);
        $this->assertEquals(1, $this->db->getTransactionDepth());

        // Adapter transaction management
        $this->db->beginTransaction();
        $this->assertEquals(2, $this->db->getTransactionDepth());
        $this->db->query("INSERT INTO users (username, password, email) values ('testuser266', 'password27', 'testuser26@test.com')");
        $this->db->commit();
        $this->assertTrue($this->db->isTransaction());
        $this->assertEquals(1, $this->db->getTransactionDepth());

        // Record transaction management
        $admin = new Users([
            'username' => 'testuser265',
            'password' => 'password27',
            'email'    => 'testuser26@test.com',
            'logins'   => 1
        ]);
        $admin->startTransaction();
        $this->assertEquals(2, $this->db->getTransactionDepth());
        $admin->save();
        $this->assertEquals(1, $this->db->getTransactionDepth());

        // Adapter transaction rollback, visibility
        $this->db->beginTransaction();
        $this->assertEquals(2, $this->db->getTransactionDepth());
        $this->db->query("INSERT INTO users (username, password, email) values ('testuser267', 'password27', 'testuser26@test.com')");
        $test = Users::findOne(['username' => 'testuser267']);
        $this->assertNotNull($test->id);
        $this->db->rollback();
        $this->assertTrue($this->db->isTransaction());
        $this->assertEquals(1, $this->db->getTransactionDepth());
        $test = Users::findOne(['username' => 'testuser267']);
        $this->assertNull($test->id);

        // Commits outer transaction
        $user->save();
        $this->assertFalse($this->db->isTransaction());
        $this->assertEquals(0, $this->db->getTransactionDepth());

        $this->db->disconnect();
    }

    public function testFindWhere()
    {
        $user = new Users([
            'username' => 'testuser24',
            'password' => 'password24',
            'email'    => 'testuser24@test.com',
            'logins'   => 1
        ]);
        $user->save();

        $newUsers = Users::findWhereUsername('testuser24');
        $this->assertEquals(1, $newUsers->count());
        $this->assertEquals('testuser24', $newUsers[0]->username);

        $newUsers2 = Users::findWhereUsername('testuser27');
        $this->assertEquals(0, $newUsers2->count());

        $this->db->disconnect();
    }

    public function testFindWhereConditions()
    {
        $user = new Users([
            'username' => 'testuser24',
            'password' => 'password24',
            'email'    => 'testuser24@test.com',
            'logins'   => 1
        ]);
        $user->save();

        $users = Users::findWhereGreaterThan('logins', 0);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereGreaterThanOrEqual('logins', 1);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereLessThan('logins', 2);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereLessThanOrEqual('logins', 1);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereEquals('logins', 1);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNotEquals('logins', -1);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereIn('logins', [1]);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNotIn('logins', [10000000]);
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNull('logins');
        $this->assertEquals(0, $users->count());

        $users = Users::findWhereNotNull('logins');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereBetween('logins', '(0, 10)');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNotBetween('logins', '(1000000, 1000010)');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereLike('username', 'testuser%');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNotLike('username', 'baduser%');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereLike('username', '%testuser24');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $users = Users::findWhereNotLike('username', '%baduser');
        $this->assertGreaterThanOrEqual(1, $users->count());

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $schema->execute();

        $this->db->disconnect();
    }

    public function testFindWhereConditionsDoNotTriggerDeprecation()
    {
        $user = new Users([
            'username' => 'testuser25',
            'password' => 'password25',
            'email'    => 'testuser25@test.com',
            'logins'   => 1
        ]);
        $user->save();

        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            if ($errno === E_USER_DEPRECATED) {
                $deprecations[] = $errstr;
            }
            return true;
        });

        try {
            $results = [
                'GreaterThan'        => Users::findWhereGreaterThan('logins', 0),
                'GreaterThanOrEqual' => Users::findWhereGreaterThanOrEqual('logins', 1),
                'LessThan'           => Users::findWhereLessThan('logins', 2),
                'LessThanOrEqual'    => Users::findWhereLessThanOrEqual('logins', 1),
                'Equals'             => Users::findWhereEquals('logins', 1),
                'NotEquals'          => Users::findWhereNotEquals('logins', -1),
                'In'                 => Users::findWhereIn('logins', [1]),
                'NotIn'              => Users::findWhereNotIn('logins', [10000000]),
                'NotNull'            => Users::findWhereNotNull('logins'),
                'Between'            => Users::findWhereBetween('logins', '(0, 10)'),
                'NotBetween'         => Users::findWhereNotBetween('logins', '(1000000, 1000010)'),
                'Like'               => Users::findWhereLike('username', 'testuser%'),
                'LikeLeading'        => Users::findWhereLike('username', '%testuser25'),
                'NotLike'            => Users::findWhereNotLike('username', 'baduser%'),
                'NotLikeLeading'     => Users::findWhereNotLike('username', '%baduser'),
            ];
            $null = Users::findWhereNull('logins');
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(
            $deprecations,
            "findWhereX() must not emit deprecations from pop-db's own internals: " . implode(' | ', $deprecations)
        );

        foreach ($results as $condition => $collection) {
            $this->assertGreaterThanOrEqual(1, $collection->count(), $condition . ' returned no rows');
        }
        $this->assertEquals(0, $null->count());

        // BETWEEN/NOT BETWEEN also accept an unambiguous 2-element array
        $this->assertGreaterThanOrEqual(1, Users::findWhereBetween('logins', [0, 10])->count());
        $this->assertGreaterThanOrEqual(1, Users::findWhereNotBetween('logins', [1000000, 1000010])->count());

        $schema = $this->db->createSchema();
        $schema->dropIfExists('users');
        $schema->execute();

        $this->db->disconnect();
    }

}
