<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\DlParent;
use Pop\Db\Test\TestAsset\DlChild;
use Pop\Db\Test\TestAsset\DlOneofHost;
use Pop\Db\Test\TestAsset\DlGrand1;
use Pop\Db\Test\TestAsset\DlGrand2;
use PHPUnit\Framework\TestCase;

class DeepRelationshipTest extends TestCase
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
        foreach (['dl_grand1', 'dl_grand2', 'dl_oneof_hosts', 'dl_children', 'dl_parents'] as $table) {
            $schema->dropIfExists($table);
        }
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('dl_parents')
            ->int('id', 16)->increment()
            ->varchar('name', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('dl_children')
            ->int('id', 16)->increment()
            ->int('parent_id', 16)
            ->varchar('name', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('dl_oneof_hosts')
            ->int('id', 16)->increment()
            ->varchar('name', 255)
            ->int('child_id', 16)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('dl_grand1')
            ->int('id', 16)->increment()
            ->int('child_id', 16)
            ->varchar('note', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('dl_grand2')
            ->int('id', 16)->increment()
            ->int('child_id', 16)
            ->varchar('note', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        DlParent::setDb($this->db);
        DlChild::setDb($this->db);
        DlOneofHost::setDb($this->db);
        DlGrand1::setDb($this->db);
        DlGrand2::setDb($this->db);
    }

    public function testFixturesResolveOneLevel()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $host = new DlOneofHost(['name' => 'H1', 'child_id' => $child->id]);
        $host->save();

        $g1 = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-note']);
        $g1->save();

        $g2 = new DlGrand2(['child_id' => $child->id, 'note' => 'g2-note']);
        $g2->save();

        $foundParent = DlParent::findById($parent->id);
        $this->assertEquals('P1', $foundParent->name);
        $this->assertEquals(1, $foundParent->children()->count());

        $foundHost = DlOneofHost::findById($host->id);
        $this->assertInstanceOf(DlChild::class, $foundHost->child());
        $this->assertEquals('C1', $foundHost->child()->name);

        $this->db->disconnect();
    }

    public function testHasManyEagerLoadsTwoDifferentGrandchildren()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child1 = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child1->save();
        $child2 = new DlChild(['parent_id' => $parent->id, 'name' => 'C2']);
        $child2->save();

        $g1a = new DlGrand1(['child_id' => $child1->id, 'note' => 'g1-c1']);
        $g1a->save();
        $g2a = new DlGrand2(['child_id' => $child1->id, 'note' => 'g2-c1']);
        $g2a->save();
        $g2b = new DlGrand2(['child_id' => $child2->id, 'note' => 'g2-c2']);
        $g2b->save();

        // Use getOne() (not getById()) so this actually exercises the eager-load path
        // (AbstractRecord::processWithRelationships() -> HasMany::getEagerRelationships() ->
        // hydrateChildRelationships()) that this task fixes. getById() resolves its top-level
        // 'with' relationships via HasMany::getChildren() (always non-eager), which never calls
        // HasMany::getEagerRelationships() at all — see report for details.
        $found = DlParent::with(['children.grand1', 'children.grand2'])->getOne(['id' => $parent->id]);

        $children = $found->children;
        $this->assertEquals(2, $children->count());

        foreach ($children as $child) {
            $this->assertTrue($child->hasRelationship('grand1'), 'child ' . $child->name . ' missing grand1');
            $this->assertTrue($child->hasRelationship('grand2'), 'child ' . $child->name . ' missing grand2');

            if ($child->name === 'C1') {
                $this->assertEquals(1, $child->grand1->count());
                $this->assertEquals(1, $child->grand2->count());
            } else {
                $this->assertEquals(0, $child->grand1->count());
                $this->assertEquals(1, $child->grand2->count());
            }
        }

        $this->db->disconnect();
    }

    public function testHasManyEmptyRelationshipValuesAreNotAliased()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        // Both children have zero grand1 rows, so both hit the "no eager match" fallback
        // in AbstractRelationship::hydrateChildRelationships(). Before the fix, they were
        // handed the SAME cached empty Collection instance.
        $child1 = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child1->save();
        $child2 = new DlChild(['parent_id' => $parent->id, 'name' => 'C2']);
        $child2->save();

        $found = DlParent::with(['children.grand1'])->getOne(['id' => $parent->id]);

        $children = $found->children;
        $this->assertEquals(2, $children->count());

        $c1 = null;
        $c2 = null;
        foreach ($children as $child) {
            if ($child->name === 'C1') {
                $c1 = $child;
            } else {
                $c2 = $child;
            }
        }

        $this->assertNotNull($c1);
        $this->assertNotNull($c2);
        $this->assertInstanceOf(\Pop\Db\Record\Collection::class, $c1->grand1);
        $this->assertInstanceOf(\Pop\Db\Record\Collection::class, $c2->grand1);
        $this->assertEquals(0, $c1->grand1->count());
        $this->assertEquals(0, $c2->grand1->count());

        // The core aliasing assertion: distinct objects, not the same shared instance.
        $this->assertNotEquals(spl_object_id($c1->grand1), spl_object_id($c2->grand1));

        // Mutating one child's empty collection must not affect the other's.
        $g = new DlGrand1(['child_id' => $c1->id, 'note' => 'in-memory-only']);
        $c1->grand1->push($g);

        $this->assertEquals(1, $c1->grand1->count());
        $this->assertEquals(0, $c2->grand1->count());

        $this->db->disconnect();
    }

    public function testTopLevelWithDoesNotFatalOnEmptyNestedGrandchildren()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        // Only one child, with zero matching grand1 rows. Fetching the parent via getById()
        // with a nested 'children.grand1' with-entry resolves the parent's 'children' via
        // HasMany::getChildren()'s lazy path, which internally calls $table::with(...)->getBy(...)
        // for the child records -- that inner getBy() resolves the child's own 'grand1'
        // with-entry via AbstractRecord::processWithRelationships(), which previously fell back
        // to a hardcoded [] on no match, fataling on $child->grand1->count().
        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $found = DlParent::with(['children.grand1'])->getById($parent->id);

        $this->assertInstanceOf(DlParent::class, $found);
        $children = $found->children;
        $this->assertEquals(1, $children->count());

        $foundChild = $children[0];
        $this->assertTrue($foundChild->hasRelationship('grand1'));
        $this->assertInstanceOf(\Pop\Db\Record\Collection::class, $foundChild->grand1);
        $this->assertEquals(0, $foundChild->grand1->count());

        $this->db->disconnect();
    }

    public function testHasOneEagerLoadsTwoDifferentGrandchildren()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $g1 = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-note']);
        $g1->save();
        $g2 = new DlGrand2(['child_id' => $child->id, 'note' => 'g2-note']);
        $g2->save();

        // Use getOne() (not getById()) so this actually exercises the eager-load path
        // (AbstractRecord::processWithRelationships() -> HasOne::getEagerRelationships() ->
        // hydrateChildRelationships()). getById() resolves its top-level 'with' relationships
        // via getWithRelationships(false), i.e. HasOne::getChild()'s lazy path, which never
        // calls HasOne::getEagerRelationships() at all.
        $found = DlParent::with(['firstChild.grand1', 'firstChild.grand2'])->getOne(['id' => $parent->id]);

        $this->assertInstanceOf(DlChild::class, $found->firstChild);
        $this->assertTrue($found->firstChild->hasRelationship('grand1'));
        $this->assertTrue($found->firstChild->hasRelationship('grand2'));
        $this->assertEquals(1, $found->firstChild->grand1->count());
        $this->assertEquals(1, $found->firstChild->grand2->count());

        $this->db->disconnect();
    }

    public function testHasOneEmptyRelationshipResolvesToNullWithoutRedundantQuery()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        // No DlChild rows at all for this parent, so the eager-loaded 'firstChild'
        // relationship correctly resolves to null (HasOne::getEmptyRelationshipValue()).
        // Before the fix, AbstractRecord::__get()/hasRelationship()/__isset() used
        // isset($this->relationships[$name]), which is false for a key set to null,
        // so __get() fell through to method_exists() and re-invoked $this->firstChild()
        // fresh (a redundant lazy, non-eager query) instead of returning the already-
        // resolved null.
        $found = DlParent::with('firstChild')->getOne(['id' => $parent->id]);

        // The relationship WAS resolved (to null) -- hasRelationship() must report true,
        // not false, even though the resolved value is null.
        $this->assertTrue($found->hasRelationship('firstChild'));

        // Direct property access must return the correctly-computed null, not a
        // freshly-constructed empty DlChild instance from a redundant lazy call.
        $this->assertNull($found->firstChild);

        // __isset() must also report true: the relationship name was resolved.
        $this->assertTrue(isset($found->firstChild));

        // getRelationship() must agree.
        $this->assertNull($found->getRelationship('firstChild'));

        $this->db->disconnect();
    }

    public function testHasManyEmptyRelationshipStillResolvesCorrectlyAfterArrayKeyExistsFix()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        // No DlChild rows at all, so the eager-loaded 'children' HasMany relationship
        // correctly resolves to an empty Collection (HasMany::getEmptyRelationshipValue()).
        // This confirms the isset() -> array_key_exists() fix doesn't break the
        // already-working HasMany/Collection case.
        $found = DlParent::with('children')->getOne(['id' => $parent->id]);

        $this->assertTrue($found->hasRelationship('children'));

        $children = $found->children;
        $this->assertInstanceOf(\Pop\Db\Record\Collection::class, $children);
        $this->assertEquals(0, $children->count());

        // Confirm it's the same instance stored via setRelationship(), i.e. accessing
        // the property twice doesn't trigger a redundant re-resolution.
        $this->assertSame($children, $found->children);

        $this->assertTrue(isset($found->children));
        $this->assertSame($children, $found->getRelationship('children'));

        $this->db->disconnect();
    }

    public function testHasOneOfEagerLoadsTwoDifferentGrandchildren()
    {
        $child = new DlChild(['parent_id' => 0, 'name' => 'C1']);
        $child->save();

        $host = new DlOneofHost(['name' => 'H1', 'child_id' => $child->id]);
        $host->save();

        $g1 = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-note']);
        $g1->save();
        $g2 = new DlGrand2(['child_id' => $child->id, 'note' => 'g2-note']);
        $g2->save();

        // Use getOne() (not getById()) so this actually exercises the eager-load path
        // (AbstractRecord::processWithRelationships() -> HasOneOf::getEagerRelationships() ->
        // hydrateChildRelationships()). getById() resolves its top-level 'with' relationships
        // via getWithRelationships(false), i.e. HasOneOf::getChild()'s lazy path, which never
        // calls HasOneOf::getEagerRelationships() at all.
        $found = DlOneofHost::with(['child.grand1', 'child.grand2'])->getOne(['id' => $host->id]);

        $this->assertInstanceOf(DlChild::class, $found->child);
        $this->assertTrue($found->child->hasRelationship('grand1'));
        $this->assertTrue($found->child->hasRelationship('grand2'));
        $this->assertEquals(1, $found->child->grand1->count());
        $this->assertEquals(1, $found->child->grand2->count());

        $this->db->disconnect();
    }

    public function testHasOneOfEmptyRelationshipResolvesToNullWithoutRedundantQuery()
    {
        $host = new DlOneofHost(['name' => 'H1', 'child_id' => 999999]);
        $host->save();

        // The child_id points to a non-existent DlChild record, so the eager-loaded 'child'
        // relationship correctly resolves to null (HasOneOf::getEmptyRelationshipValue()).
        // Before the fix, AbstractRecord::__get()/hasRelationship()/__isset() used
        // isset($this->relationships[$name]), which is false for a key set to null,
        // so __get() fell through to method_exists() and re-invoked $this->child()
        // fresh (a redundant lazy, non-eager query) instead of returning the already-
        // resolved null.
        $found = DlOneofHost::with('child')->getOne(['id' => $host->id]);

        // The relationship WAS resolved (to null) -- hasRelationship() must report true,
        // not false, even though the resolved value is null.
        $this->assertTrue($found->hasRelationship('child'));

        // Direct property access must return the correctly-computed null, not a
        // freshly-constructed DlChild instance from a redundant lazy call.
        $this->assertNull($found->child);

        // __isset() must also report true: the relationship name was resolved.
        $this->assertTrue(isset($found->child));

        // getRelationship() must agree.
        $this->assertNull($found->getRelationship('child'));

        $this->db->disconnect();
    }

    public function testBelongsToEagerLoadsTwoDifferentGrandchildren()
    {
        // Decoy parent, so the parent ids and child ids deliberately diverge: the real parent
        // gets id 2 while its children get ids 1 and 2. If the eager-load path keyed the
        // BelongsTo lookup off the CHILD's own primary key (id 1) rather than its foreign key
        // (parent_id 2), it would silently resolve to this decoy instead.
        $decoyParent = new DlParent(['name' => 'DECOY']);
        $decoyParent->save();

        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $siblingChild = new DlChild(['parent_id' => $parent->id, 'name' => 'C2']);
        $siblingChild->save();

        $this->assertNotEquals($parent->id, $child->id);

        // Use getOne() (not getById()) so this actually exercises the eager-load path
        // (AbstractRecord::processWithRelationships() -> BelongsTo::getEagerRelationships() ->
        // hydrateChildRelationships()). getById() resolves its top-level 'with' relationships
        // via getWithRelationships(false), i.e. BelongsTo::getParent()'s lazy path, which never
        // calls BelongsTo::getEagerRelationships() at all.
        $found = DlChild::with(['parentRecord.children', 'parentRecord.firstChild'])->getOne(['id' => $child->id]);

        $this->assertInstanceOf(DlParent::class, $found->parentRecord);
        $this->assertEquals($parent->id, $found->parentRecord->id);
        $this->assertEquals('P1', $found->parentRecord->name);
        $this->assertTrue($found->parentRecord->hasRelationship('children'));
        $this->assertTrue($found->parentRecord->hasRelationship('firstChild'));
        $this->assertEquals(2, $found->parentRecord->children->count());
        $this->assertInstanceOf(DlChild::class, $found->parentRecord->firstChild);
        // NOTE: which of the parent's two children 'firstChild' resolves to is deliberately not
        // asserted -- the eager HasOne path keeps the LAST matching row while the lazy path keeps
        // the first. That pre-existing inconsistency is out of scope here; all that matters for
        // this test is that the relationship resolved to a DlChild belonging to the right parent.
        $this->assertEquals($parent->id, $found->parentRecord->firstChild->parent_id);

        $this->db->disconnect();
    }

    public function testBelongsToEmptyRelationshipResolvesToNullWithoutRedundantQuery()
    {
        $child = new DlChild(['parent_id' => 999999, 'name' => 'C1']);
        $child->save();

        // The parent_id points to a non-existent DlParent record, so the eager-loaded 'parentRecord'
        // relationship correctly resolves to null (BelongsTo::getEmptyRelationshipValue()).
        // Before the fix, AbstractRecord::__get()/hasRelationship()/__isset() used
        // isset($this->relationships[$name]), which is false for a key set to null,
        // so __get() fell through to method_exists() and re-invoked $this->parentRecord()
        // fresh (a redundant lazy, non-eager query) instead of returning the already-
        // resolved null.
        $found = DlChild::with('parentRecord')->getOne(['id' => $child->id]);

        // The relationship WAS resolved (to null) -- hasRelationship() must report true,
        // not false, even though the resolved value is null.
        $this->assertTrue($found->hasRelationship('parentRecord'));

        // Direct property access must return the correctly-computed null, not a
        // freshly-constructed DlParent instance from a redundant lazy call.
        $this->assertNull($found->parentRecord);

        // __isset() must also report true: the relationship name was resolved.
        $this->assertTrue(isset($found->parentRecord));

        // getRelationship() must agree.
        $this->assertNull($found->getRelationship('parentRecord'));

        $this->db->disconnect();
    }

    /**
     * Build three parents and three children whose parent_id values are a deliberate
     * permutation of the child ids (C1 -> P3, C2 -> P1, C3 -> P2), each fronted by a host
     * row. Any implementation that resolves a nested "to-one by foreign key" relationship
     * off the leaf record's own primary key instead of its foreign key column will still
     * find *a* parent for every child -- just the wrong one -- so the returned names are
     * what actually discriminate.
     *
     * @return array
     */
    protected function seedPermutedParents(): array
    {
        $parents = [];
        foreach (['P1', 'P2', 'P3'] as $name) {
            $parent = new DlParent(['name' => $name]);
            $parent->save();
            $parents[$name] = $parent;
        }

        $expected = ['C1' => 'P3', 'C2' => 'P1', 'C3' => 'P2'];
        $children = [];

        foreach ($expected as $childName => $parentName) {
            $child = new DlChild(['parent_id' => $parents[$parentName]->id, 'name' => $childName]);
            $child->save();
            $children[$childName] = $child;

            $host = new DlOneofHost(['name' => 'H-' . $childName, 'child_id' => $child->id]);
            $host->save();

            // The whole point of the permutation: the child's own id never equals its parent_id.
            $this->assertNotEquals($child->id, $child->parent_id);
        }

        return $expected;
    }

    public function testNestedBelongsToChildUsesForeignKeyNotLeafPrimaryKey()
    {
        $expected = $this->seedPermutedParents();

        // 'child' is a HasOneOf on DlOneofHost; 'parentRecord' is a BelongsTo nested UNDER it.
        // AbstractRelationship::hydrateChildRelationships() must key that nested BelongsTo off
        // each leaf DlChild's parent_id, not off the leaf's own primary key.
        $hosts = DlOneofHost::with('child.parentRecord')->getBy(null, ['order' => 'id ASC']);

        $this->assertEquals(3, $hosts->count());

        foreach ($hosts as $host) {
            $child = $host->child;
            $this->assertInstanceOf(DlChild::class, $child);
            $this->assertTrue($child->hasRelationship('parentRecord'));
            $this->assertInstanceOf(
                DlParent::class, $child->parentRecord, 'child ' . $child->name . ' has no parentRecord'
            );
            $this->assertEquals(
                $expected[$child->name], $child->parentRecord->name,
                'child ' . $child->name . ' resolved to the wrong parent'
            );
            $this->assertEquals($child->parent_id, $child->parentRecord->id);
        }

        $this->db->disconnect();
    }

    public function testNestedHasOneOfChildUsesForeignKeyNotLeafPrimaryKey()
    {
        $expected = $this->seedPermutedParents();

        // Same shape as above, but the nested child is a HasOneOf ('parentOneOf') rather than
        // a BelongsTo -- both need the leaf's foreign key column, not the leaf's primary key.
        $hosts = DlOneofHost::with('child.parentOneOf')->getBy(null, ['order' => 'id ASC']);

        $this->assertEquals(3, $hosts->count());

        foreach ($hosts as $host) {
            $child = $host->child;
            $this->assertInstanceOf(DlChild::class, $child);
            $this->assertTrue($child->hasRelationship('parentOneOf'));
            $this->assertInstanceOf(
                DlParent::class, $child->parentOneOf, 'child ' . $child->name . ' has no parentOneOf'
            );
            $this->assertEquals(
                $expected[$child->name], $child->parentOneOf->name,
                'child ' . $child->name . ' resolved to the wrong parent'
            );
            $this->assertEquals($child->parent_id, $child->parentOneOf->id);
        }

        $this->db->disconnect();
    }

    public function testNestedMixedToOneAndToManyChildrenEachUseTheirOwnLookupColumn()
    {
        $expected = $this->seedPermutedParents();

        // Three differently-typed nested children under the same parent relationship:
        // 'parentRecord' (BelongsTo -> keyed by parent_id), 'parentOneOf' (HasOneOf -> keyed by
        // parent_id) and 'grand1' (HasMany -> keyed by the leaf's own primary key). The lookup
        // column must be decided per relationship name, not once for all of them.
        foreach (['C1', 'C2', 'C3'] as $childName) {
            $child = DlChild::findOne(['name' => $childName]);
            $g = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-' . $childName]);
            $g->save();
        }

        $hosts = DlOneofHost::with(['child.parentRecord', 'child.parentOneOf', 'child.grand1'])
            ->getBy(null, ['order' => 'id ASC']);

        $this->assertEquals(3, $hosts->count());

        foreach ($hosts as $host) {
            $child = $host->child;
            $this->assertEquals($expected[$child->name], $child->parentRecord->name);
            $this->assertEquals($expected[$child->name], $child->parentOneOf->name);
            $this->assertInstanceOf(\Pop\Db\Record\Collection::class, $child->grand1);
            $this->assertEquals(1, $child->grand1->count());
            $this->assertEquals('g1-' . $child->name, $child->grand1[0]->note);
        }

        $this->db->disconnect();
    }

    public function testFourLevelChainWithToOneLeafResolvesThroughTheEagerPath()
    {
        // host -> child (HasOneOf) -> grand1 (HasMany) -> owner (BelongsTo back to the child).
        // Backs the README's claim that nesting isn't limited to one level, and exercises a
        // to-one nested child two levels below the top-level relationship.
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $g1a = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-a']);
        $g1a->save();
        $g1b = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-b']);
        $g1b->save();

        $host = new DlOneofHost(['name' => 'H1', 'child_id' => $child->id]);
        $host->save();

        $found = DlOneofHost::with('child.grand1.owner')->getOne(['id' => $host->id]);

        $this->assertInstanceOf(DlChild::class, $found->child);
        $this->assertEquals(2, $found->child->grand1->count());

        foreach ($found->child->grand1 as $grand) {
            $this->assertTrue($grand->hasRelationship('owner'));
            $this->assertInstanceOf(DlChild::class, $grand->owner);
            $this->assertEquals($child->id, $grand->owner->id);
            $this->assertEquals('C1', $grand->owner->name);
        }

        $this->db->disconnect();
    }

    public function testSingleChainThreeLevelsDeepStillWorks()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $g1a = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-a']);
        $g1a->save();
        $g1b = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-b']);
        $g1b->save();

        $found = DlParent::with('children.grand1')->getById($parent->id);

        $this->assertEquals(1, $found->children->count());
        $foundChild = $found->children[0];
        $this->assertTrue($foundChild->hasRelationship('grand1'));
        $this->assertEquals(2, $foundChild->grand1->count());

        $this->db->disconnect();
    }

    public function testMixedBareAndDottedRequestForSameNameMerges()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $g1 = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-note']);
        $g1->save();

        $found = DlParent::with(['children', 'children.grand1'])->getById($parent->id);

        $this->assertEquals(1, $found->children->count());
        $foundChild = $found->children[0];
        $this->assertTrue($foundChild->hasRelationship('grand1'));
        $this->assertEquals(1, $foundChild->grand1->count());

        $this->db->disconnect();
    }

    public function testLazyPathCarriesMultipleQueuedChildren()
    {
        $parent = new DlParent(['name' => 'P1']);
        $parent->save();

        $child = new DlChild(['parent_id' => $parent->id, 'name' => 'C1']);
        $child->save();

        $g1 = new DlGrand1(['child_id' => $child->id, 'note' => 'g1-note']);
        $g1->save();
        $g2 = new DlGrand2(['child_id' => $child->id, 'note' => 'g2-note']);
        $g2->save();

        // getById() resolves with()-queued relationships lazily ($eager = false internally),
        // exercising the non-eager code path rather than the batch getBy()/getEagerRelationships() path.
        $found = DlParent::with(['children.grand1', 'children.grand2'])->getById($parent->id);

        $foundChild = $found->children[0];
        $this->assertTrue($foundChild->hasRelationship('grand1'));
        $this->assertTrue($foundChild->hasRelationship('grand2'));
        $this->assertEquals(1, $foundChild->grand1->count());
        $this->assertEquals(1, $foundChild->grand2->count());

        $this->db->disconnect();
    }

    /**
     * Final cleanup: setUp() re-creates the dl_* tables before every test, so this drops them
     * once the suite is done rather than leaving them behind in the test database. Follows the
     * same testFinal() convention as RelationshipTest.
     */
    public function testFinal()
    {
        $var = 1;
        $this->assertEquals(1, $var);

        $this->db->connect();

        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();

        foreach (['dl_grand1', 'dl_grand2', 'dl_oneof_hosts', 'dl_children', 'dl_parents'] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->execute();

        $this->assertFalse($this->db->hasTable('dl_parents'));
        $this->assertFalse($this->db->hasTable('dl_children'));
        $this->assertFalse($this->db->hasTable('dl_oneof_hosts'));
        $this->assertFalse($this->db->hasTable('dl_grand1'));
        $this->assertFalse($this->db->hasTable('dl_grand2'));

        $this->db->disconnect();
    }

}
