<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Exception;
use Pop\Db\Record;
use Pop\Db\Test\TestAsset\MockData;
use Pop\Db\Test\TestAsset\Users;
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

    public function testSaveMultiple()
    {
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

    public function testDeleteMultiple()
    {
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

    public function testGetTotal()
    {
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

}