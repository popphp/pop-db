<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Record\Collection;
use Pop\Db\Test\TestAsset\ErNote;
use Pop\Db\Test\TestAsset\ErOrg;
use Pop\Db\Test\TestAsset\ErPost;
use Pop\Db\Test\TestAsset\ErRole;
use Pop\Db\Test\TestAsset\ErUser;
use Pop\Db\Test\TestAsset\ErUserInfo;
use PHPUnit\Framework\TestCase;

/**
 * Eager-loading (Record::with()) coverage for all four relationship types.
 *
 * Eager loading has to generate its own bound-parameter placeholders, and every dialect spells
 * those differently ('?' on MySQL/SQL Server, '$1'/'$2'/... on PostgreSQL, ':name' on
 * SQLite and on every PDO connection). A single-adapter test therefore proves nothing about
 * eager loading in general, so this case is written once here and run by one concrete subclass
 * per adapter (see EagerRelationshipMysqlTest / EagerRelationshipPgsqlTest /
 * EagerRelationshipSqliteTest / EagerRelationshipPdoSqliteTest).
 *
 * Subclasses only supply the connection.
 */
abstract class EagerRelationshipTestCase extends TestCase
{

    protected ?AbstractAdapter $db = null;

    /**
     * SQLite database file to delete in tearDown(), if the concrete adapter under test uses one
     */
    protected ?string $sqliteFile = null;

    /**
     * Connect the adapter under test
     */
    abstract protected function createDb(): AbstractAdapter;

    public function setUp(): void
    {
        $this->db = $this->createDb();

        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();
        foreach (['er_notes', 'er_orgs', 'er_posts', 'er_user_info', 'er_users', 'er_roles'] as $table) {
            $schema->dropIfExists($table);
        }
        $this->executeSchema($schema);

        $schema->create('er_roles')
            ->int('id', 16)->increment()
            ->varchar('name', 255)
            ->primary('id');
        $this->executeSchema($schema);

        $schema->create('er_users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->int('role_id', 16)
            ->primary('id');
        $this->executeSchema($schema);

        $schema->create('er_user_info')
            ->int('id', 16)->increment()
            ->int('user_id', 16)
            ->varchar('notes', 255)
            ->primary('id');
        $this->executeSchema($schema);

        $schema->create('er_posts')
            ->int('id', 16)->increment()
            ->int('user_id', 16)
            ->varchar('title', 255)
            ->varchar('status', 255)
            ->primary('id');
        $this->executeSchema($schema);

        $schema->create('er_orgs')
            ->int('org_id', 16)
            ->int('branch_id', 16)
            ->varchar('name', 255)
            ->primary(['org_id', 'branch_id']);
        $this->executeSchema($schema);

        $schema->create('er_notes')
            ->int('id', 16)->increment()
            ->int('note_org_id', 16)
            ->int('note_branch_id', 16)
            ->varchar('note', 255)
            ->primary('id');
        $this->executeSchema($schema);

        foreach ([ErRole::class, ErUser::class, ErUserInfo::class, ErPost::class, ErOrg::class, ErNote::class] as $class) {
            $class::setDb($this->db);
        }

        $this->seed();
    }

    public function tearDown(): void
    {
        $this->db->disconnect();

        if (($this->sqliteFile !== null) && file_exists($this->sqliteFile)) {
            unlink($this->sqliteFile);
        }
    }

    protected function executeSchema($schema): void
    {
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();
    }

    protected function seed(): void
    {
        (new ErRole(['name' => 'admin']))->save();
        (new ErRole(['name' => 'editor']))->save();

        foreach ([['user1', 1], ['user2', 2]] as [$username, $roleId]) {
            $user = new ErUser(['username' => $username, 'role_id' => $roleId]);
            $user->save();

            (new ErUserInfo(['user_id' => $user->id, 'notes' => 'notes for ' . $username]))->save();
            (new ErPost(['user_id' => $user->id, 'title' => $username . ' post A', 'status' => 'live']))->save();
            (new ErPost(['user_id' => $user->id, 'title' => $username . ' post B', 'status' => 'draft']))->save();
        }

        (new ErOrg(['org_id' => 1, 'branch_id' => 10, 'name' => 'Org A']))->save();
        (new ErOrg(['org_id' => 2, 'branch_id' => 20, 'name' => 'Org B']))->save();

        (new ErNote(['note_org_id' => 1, 'note_branch_id' => 10, 'note' => 'note a1']))->save();
        (new ErNote(['note_org_id' => 1, 'note_branch_id' => 10, 'note' => 'note a2']))->save();
        (new ErNote(['note_org_id' => 2, 'note_branch_id' => 20, 'note' => 'note b1']))->save();
    }

/*
 * Single-column keys, one relationship type per test
 */

    public function testEagerHasOne()
    {
        $users = ErUser::with('info')->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users->count());
        $this->assertInstanceOf(ErUserInfo::class, $users[0]->info);
        $this->assertEquals('notes for user1', $users[0]->info->notes);
        $this->assertEquals('notes for user2', $users[1]->info->notes);
    }

    public function testEagerHasMany()
    {
        $users = ErUser::with('posts')->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users->count());
        $this->assertInstanceOf(Collection::class, $users[0]->posts);
        $this->assertEquals(2, $users[0]->posts->count());
        $this->assertEquals(2, $users[1]->posts->count());
        $this->assertEquals('user1 post A', $users[0]->posts[0]->title);
        $this->assertEquals('user2 post A', $users[1]->posts[0]->title);
    }

    public function testEagerHasOneOf()
    {
        $users = ErUser::with('role')->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users->count());
        $this->assertInstanceOf(ErRole::class, $users[0]->role);
        $this->assertEquals('admin', $users[0]->role->name);
        $this->assertEquals('editor', $users[1]->role->name);
    }

    public function testEagerBelongsTo()
    {
        $posts = ErPost::with('user')->getAll(['order' => 'id ASC']);

        $this->assertEquals(4, $posts->count());
        $this->assertInstanceOf(ErUser::class, $posts[0]->user);
        $this->assertEquals('user1', $posts[0]->user->username);
        $this->assertEquals('user2', $posts[3]->user->username);
    }

    public function testEagerAllRelationshipTypesAtOnce()
    {
        $users = ErUser::with(['info', 'posts', 'role'])->getAll(['order' => 'id ASC']);

        $this->assertEquals('notes for user1', $users[0]->info->notes);
        $this->assertEquals(2, $users[0]->posts->count());
        $this->assertEquals('admin', $users[0]->role->name);
    }

/*
 * The other entry points into eager loading
 */

    public function testEagerViaGetOne()
    {
        $user = ErUser::with('posts')->getOne(['username' => 'user1']);

        $this->assertEquals('user1', $user->username);
        $this->assertEquals(2, $user->posts->count());
    }

    public function testEagerViaGetBy()
    {
        $users = ErUser::with('posts')->getBy(['username' => 'user2']);

        $this->assertEquals(1, $users->count());
        $this->assertEquals(2, $users[0]->posts->count());
    }

    public function testEagerViaFindBy()
    {
        $users = ErUser::with('role')->getBy(['role_id' => 2]);

        $this->assertEquals(1, $users->count());
        $this->assertEquals('editor', $users[0]->role->name);
    }

/*
 * Nested (dotted) eager loading
 */

    public function testEagerNestedHasManyThenBelongsTo()
    {
        $users = ErUser::with('posts.user')->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users[0]->posts->count());
        $this->assertInstanceOf(ErUser::class, $users[0]->posts[0]->user);
        $this->assertEquals('user1', $users[0]->posts[0]->user->username);
        $this->assertEquals('user2', $users[1]->posts[0]->user->username);
    }

    public function testEagerNestedFromGetById()
    {
        $user = ErUser::with('posts.user')->getById(1);

        $this->assertEquals('user1', $user->username);
        $this->assertEquals(2, $user->posts->count());
        $this->assertEquals('user1', $user->posts[0]->user->username);
    }

    public function testEagerNestedBelongsToThenHasMany()
    {
        $posts = ErPost::with('user.posts')->getAll(['order' => 'id ASC']);

        $this->assertEquals('user1', $posts[0]->user->username);
        $this->assertEquals(2, $posts[0]->user->posts->count());
    }

/*
 * Relationship options, which bind their own parameters alongside the id filter
 */

    public function testEagerHasManyWithColumnsOption()
    {
        $users = ErUser::with('posts', ['columns' => ['status' => 'live']])->getAll(['order' => 'id ASC']);

        $this->assertEquals(1, $users[0]->posts->count());
        $this->assertEquals('user1 post A', $users[0]->posts[0]->title);
        $this->assertEquals(1, $users[1]->posts->count());
        $this->assertEquals('user2 post A', $users[1]->posts[0]->title);
    }

    public function testEagerHasManyWithOrderOption()
    {
        $users = ErUser::with('posts', ['order' => 'id DESC'])->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users[0]->posts->count());
        $this->assertEquals('user1 post B', $users[0]->posts[0]->title);
        $this->assertEquals('user1 post A', $users[0]->posts[1]->title);
    }

    public function testEagerHasManyWithLimitOption()
    {
        // The limit applies to the one bulk query, not per parent record.
        $users = ErUser::with('posts', ['order' => 'id ASC', 'limit' => 3])->getAll(['order' => 'id ASC']);

        $this->assertEquals(3, $users[0]->posts->count() + $users[1]->posts->count());
        $this->assertEquals('user1 post A', $users[0]->posts[0]->title);
    }

    public function testEagerHasManyWithSelectOption()
    {
        $users = ErUser::with('posts', ['select' => ['id', 'user_id', 'title']])->getAll(['order' => 'id ASC']);

        $this->assertEquals(2, $users[0]->posts->count());
        $this->assertEquals('user1 post A', $users[0]->posts[0]->title);
    }

/*
 * Composite (multi-column) keys
 */

    public function testEagerCompositeHasMany()
    {
        $orgs = ErOrg::with('notes')->getAll(['order' => 'org_id ASC']);

        $this->assertEquals(2, $orgs->count());
        $this->assertEquals(2, $orgs[0]->notes->count());
        $this->assertEquals('note a1', $orgs[0]->notes[0]->note);
        $this->assertEquals(1, $orgs[1]->notes->count());
        $this->assertEquals('note b1', $orgs[1]->notes[0]->note);
    }

    public function testEagerCompositeHasOne()
    {
        // A "has one" eager map keeps the LAST row it sees per key, so ordering descending
        // makes the lowest-id note the one that survives, on every adapter.
        $orgs = ErOrg::with('firstNote', ['order' => 'id DESC'])->getAll(['order' => 'org_id ASC']);

        $this->assertInstanceOf(ErNote::class, $orgs[0]->firstNote);
        $this->assertEquals('note a1', $orgs[0]->firstNote->note);
        $this->assertEquals('note b1', $orgs[1]->firstNote->note);
    }

    public function testEagerCompositeBelongsTo()
    {
        $notes = ErNote::with('org')->getAll(['order' => 'id ASC']);

        $this->assertEquals(3, $notes->count());
        $this->assertInstanceOf(ErOrg::class, $notes[0]->org);
        $this->assertEquals('Org A', $notes[0]->org->name);
        $this->assertEquals('Org B', $notes[2]->org->name);
    }

    public function testEagerCompositeHasOneOf()
    {
        $notes = ErNote::with('orgOneOf')->getAll(['order' => 'id ASC']);

        $this->assertInstanceOf(ErOrg::class, $notes[0]->orgOneOf);
        $this->assertEquals('Org A', $notes[0]->orgOneOf->name);
        $this->assertEquals('Org B', $notes[2]->orgOneOf->name);
    }

    public function testEagerCompositeHasManyWithColumnsOption()
    {
        $orgs = ErOrg::with('notes', ['columns' => ['note' => 'note a2']])->getAll(['order' => 'org_id ASC']);

        $this->assertEquals(1, $orgs[0]->notes->count());
        $this->assertEquals('note a2', $orgs[0]->notes[0]->note);
        $this->assertEquals(0, $orgs[1]->notes->count());
    }

    public function testEagerCompositeNested()
    {
        $orgs = ErOrg::with('notes.org')->getAll(['order' => 'org_id ASC']);

        $this->assertEquals(2, $orgs[0]->notes->count());
        $this->assertInstanceOf(ErOrg::class, $orgs[0]->notes[0]->org);
        $this->assertEquals('Org A', $orgs[0]->notes[0]->org->name);
    }

}
