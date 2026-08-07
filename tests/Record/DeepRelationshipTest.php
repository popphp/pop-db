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

        $found = DlParent::with(['firstChild.grand1', 'firstChild.grand2'])->getById($parent->id);

        $this->assertInstanceOf(DlChild::class, $found->firstChild);
        $this->assertTrue($found->firstChild->hasRelationship('grand1'));
        $this->assertTrue($found->firstChild->hasRelationship('grand2'));
        $this->assertEquals(1, $found->firstChild->grand1->count());
        $this->assertEquals(1, $found->firstChild->grand2->count());

        $this->db->disconnect();
    }

}
