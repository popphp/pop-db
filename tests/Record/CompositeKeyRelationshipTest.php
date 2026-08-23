<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\CkOrg;
use Pop\Db\Test\TestAsset\CkNote;
use Pop\Db\Test\TestAsset\CkTicket;
use PHPUnit\Framework\TestCase;

class CompositeKeyRelationshipTest extends TestCase
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
        foreach (['ck_tickets', 'ck_notes', 'ck_orgs'] as $table) {
            $schema->dropIfExists($table);
        }
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('ck_orgs')
            ->int('org_id', 16)
            ->int('branch_id', 16)
            ->varchar('name', 255)
            ->primary(['org_id', 'branch_id']);
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('ck_notes')
            ->int('id', 16)->increment()
            ->int('note_org_id', 16)
            ->int('note_branch_id', 16)
            ->varchar('note', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        $schema->create('ck_tickets')
            ->int('ticket_id', 16)
            ->int('ticket_rev', 16)
            ->int('ticket_org_id', 16)
            ->int('ticket_branch_id', 16)
            ->int('parent_ticket_id', 16)
            ->int('parent_ticket_rev', 16)
            ->varchar('subject', 255)
            ->primary(['ticket_id', 'ticket_rev']);
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        CkOrg::setDb($this->db);
        CkNote::setDb($this->db);
        CkTicket::setDb($this->db);
    }

    public function testCompositePrimaryKeyRoundTrip()
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $found = CkOrg::findById([1, 2]);

        $this->assertInstanceOf(CkOrg::class, $found);
        $this->assertEquals('Org A', $found->name);
        $this->assertEquals(['org_id', 'branch_id'], $found->getPrimaryKeys());

        $this->db->disconnect();
    }

    public function testHasManyLazyCompositeKey()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $notesForA = $orgA->notes();

        $this->assertInstanceOf('Pop\Db\Record\Collection', $notesForA);
        $this->assertEquals(1, $notesForA->count());
        $this->assertEquals('Note for A', $notesForA[0]->note);

        $this->db->disconnect();
    }

    public function testHasManyEagerCompositeKeyDistinguishesTransposedKeys()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasMany($orgA, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id']);
        $results      = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertArrayHasKey($key, $results);
        $this->assertEquals(1, $results[$key]->count());
        $this->assertEquals('Note for A', $results[$key][0]->note);

        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);
        $this->assertArrayHasKey($otherKey, $results);
        $this->assertEquals('Note for B', $results[$otherKey][0]->note);

        $this->db->disconnect();
    }

    public function testHasManyEagerCompositeKeyWithColumnsFilterAndsCorrectly()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasMany(
            $orgA,
            'Pop\Db\Test\TestAsset\CkNote',
            ['note_org_id', 'note_branch_id'],
            ['columns' => ['note' => 'Note for A']]
        );
        $results = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        // The columns filter must be ANDed against the composite tuple match
        // (not OR'd), and params must bind positionally to the correct
        // columns: only the [1, 2] tuple (whose note matches the filter)
        // should be present, and its note must be the correct value.
        $key      = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);

        $this->assertArrayHasKey($key, $results);
        $this->assertEquals(1, $results[$key]->count());
        $this->assertEquals('Note for A', $results[$key][0]->note);
        $this->assertArrayNotHasKey($otherKey, $results);

        $this->db->disconnect();
    }

    public function testHasOneLazyCompositeKey()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $found = $orgA->firstNote();

        $this->assertInstanceOf('Pop\Db\Test\TestAsset\CkNote', $found);
        $this->assertEquals('Note for A', $found->note);

        $this->db->disconnect();
    }

    public function testHasOneEagerCompositeKeyDistinguishesTransposedKeys()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasOne($orgA, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id']);
        $results      = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals('Note for A', $results[$key]->note);

        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);
        $this->assertEquals('Note for B', $results[$otherKey]->note);

        $this->db->disconnect();
    }

    public function testAssertTupleCardinalityIsANoopForEmptyIds()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();

        // An empty $ids list has nothing to validate the cardinality of - the internal
        // callers (e.g. hydrateChildRelationships()) already guard against calling
        // getEagerRelationships() at all when $ids is empty, so this is exercised
        // directly against the protected method rather than through that public path
        $relationship = new \Pop\Db\Record\Relationships\HasOne($orgA, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id']);
        $method        = new \ReflectionMethod($relationship, 'assertTupleCardinality');
        $method->invoke($relationship, [], ['note_org_id', 'note_branch_id']);

        $this->addToAssertionCount(1);
        $this->db->disconnect();
    }

    public function testAbstractRelationshipDefaultEmptyValueIsEmptyArray()
    {
        $relationship = new class('ForeignTable', 'foreign_id') extends \Pop\Db\Record\Relationships\AbstractRelationship {
            public function getEagerRelationships(array $ids): array
            {
                return [];
            }
        };
        $this->assertEquals([], $relationship->getEmptyRelationshipValue());
    }

    public function testHasOneOfLazyCompositeKey()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        $found = $noteA->orgOneOf();

        $this->assertInstanceOf(CkOrg::class, $found);
        $this->assertEquals('Org A', $found->name);

        $this->db->disconnect();
    }

    public function testHasOneOfEagerCompositeKeyDistinguishesTransposedKeys()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        $relationship = new \Pop\Db\Record\Relationships\HasOneOf($noteA, 'Pop\Db\Test\TestAsset\CkOrg', ['note_org_id', 'note_branch_id']);
        $results      = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals('Org A', $results[$key]->name);

        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);
        $this->assertEquals('Org B', $results[$otherKey]->name);

        $this->db->disconnect();
    }

    public function testHasOneOfEagerCompositeKeyHydratesNestedChildren()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        // CkOrg's primary key is composite (['org_id', 'branch_id']) — deliberately named
        // differently from CkNote's foreign key columns ('note_org_id'/'note_branch_id'),
        // so a mix-up between the two is caught rather than masked. The leaf
        // records returned by this HasOneOf are themselves keyed by a composite
        // column when hydrateChildRelationships() resolves their own nested
        // 'notes' child (a HasMany already composite-aware since Task 2). This
        // exercises AbstractRelationship::hydrateChildRelationships()'s composite
        // branch end-to-end, not just its no-crash guard.
        $relationship = new \Pop\Db\Record\Relationships\HasOneOf($noteA, 'Pop\Db\Test\TestAsset\CkOrg', ['note_org_id', 'note_branch_id']);
        $relationship->setChildRelationships(['notes']);
        $results = $relationship->getEagerRelationships([[1, 2]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertTrue($results[$key]->hasRelationship('notes'));
        $this->assertEquals(1, $results[$key]->notes->count());
        $this->assertEquals('Note for A', $results[$key]->notes[0]->note);

        $this->db->disconnect();
    }

    public function testBelongsToLazyCompositeKey()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        $found = $noteA->org();

        $this->assertInstanceOf(CkOrg::class, $found);
        $this->assertEquals('Org A', $found->name);

        $this->db->disconnect();
    }

    public function testBelongsToEagerCompositeKeyDistinguishesTransposedKeys()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        $relationship = new \Pop\Db\Record\Relationships\BelongsTo($noteA, 'Pop\Db\Test\TestAsset\CkOrg', ['note_org_id', 'note_branch_id']);
        $results      = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals('Org A', $results[$key]->name);

        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);
        $this->assertEquals('Org B', $results[$otherKey]->name);

        $this->db->disconnect();
    }

    public function testBelongsToCardinalityMismatchThrows()
    {
        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        $relationship = new \Pop\Db\Record\Relationships\BelongsTo($noteA, 'Pop\Db\Test\TestAsset\CkOrg', ['note_org_id']);
        $relationship->getEagerRelationships([[1]]);

        $this->db->disconnect();
    }

    public function testCardinalityMismatchThrowsThroughWithDispatch()
    {
        $note = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $note->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // CkNote::badOrg() is declared with only 1 FK column against CkOrg's 2-column
        // composite primary key, so with() dispatch should surface the mismatch.
        CkNote::with('badOrg')->getOne(['id' => $note->id]);

        $this->db->disconnect();
    }

    public function testTopLevelWithResolvesCompositeKeyHasMany()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $found = CkOrg::with('notes')->getOne(['org_id' => 1, 'branch_id' => 2]);

        $this->assertInstanceOf('Pop\Db\Record\Collection', $found->notes);
        $this->assertEquals(1, $found->notes->count());
        $this->assertEquals('Note for A', $found->notes[0]->note);

        $this->db->disconnect();
    }

    public function testTopLevelWithResolvesCompositeKeyBelongsTo()
    {
        $orgA = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $orgA->save();
        $orgB = new CkOrg(['org_id' => 2, 'branch_id' => 1, 'name' => 'Org B']);
        $orgB->save();

        $noteA = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['note_org_id' => 2, 'note_branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $found = CkNote::with('org')->getOne(['id' => $noteA->id]);

        $this->assertInstanceOf(CkOrg::class, $found->org);
        $this->assertEquals('Org A', $found->org->name);

        $this->db->disconnect();
    }

    public function testNullComponentInCompositeKeyIsSkippedNotCrashed()
    {
        $note = new CkNote(['note_org_id' => 1, 'note_branch_id' => null, 'note' => 'Orphan note']);
        $note->save();

        $found = CkNote::with('org')->getOne(['id' => $note->id]);

        $this->assertNull($found->org);

        $this->db->disconnect();
    }

    /**
     * Seed one org with a single ticket that itself has one child ticket. The child
     * deliberately belongs to a *different* org, so it is not picked up by the org's
     * own hasMany/hasOne query and can only appear via the leaf ticket's own composite
     * primary key ('ticket_id'/'ticket_rev') — never via the relationship's foreign key
     * columns ('ticket_org_id'/'ticket_branch_id'), which name columns on the org side.
     */
    protected function seedTickets(): void
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $ticket = new CkTicket([
            'ticket_id' => 7, 'ticket_rev' => 1, 'ticket_org_id' => 1, 'ticket_branch_id' => 2,
            'parent_ticket_id' => 0, 'parent_ticket_rev' => 0, 'subject' => 'Parent ticket'
        ]);
        $ticket->save();

        $childTicket = new CkTicket([
            'ticket_id' => 8, 'ticket_rev' => 1, 'ticket_org_id' => 9, 'ticket_branch_id' => 9,
            'parent_ticket_id' => 7, 'parent_ticket_rev' => 1, 'subject' => 'Child ticket'
        ]);
        $childTicket->save();
    }

    public function testHasManyEagerCompositeKeyHydratesNestedChildrenByLeafPrimaryKey()
    {
        $this->seedTickets();

        $org          = CkOrg::findById([1, 2]);
        $relationship = new \Pop\Db\Record\Relationships\HasMany(
            $org, 'Pop\Db\Test\TestAsset\CkTicket', ['ticket_org_id', 'ticket_branch_id']
        );
        $relationship->setChildRelationships(['children']);
        $results = $relationship->getEagerRelationships([[1, 2]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals(1, $results[$key]->count());

        $leafTicket = $results[$key][0];
        $this->assertEquals('Parent ticket', $leafTicket->subject);
        $this->assertEquals(1, $leafTicket->children->count());
        $this->assertEquals('Child ticket', $leafTicket->children[0]->subject);

        $this->db->disconnect();
    }

    public function testHasOneEagerCompositeKeyHydratesNestedChildrenByLeafPrimaryKey()
    {
        $this->seedTickets();

        $org          = CkOrg::findById([1, 2]);
        $relationship = new \Pop\Db\Record\Relationships\HasOne(
            $org, 'Pop\Db\Test\TestAsset\CkTicket', ['ticket_org_id', 'ticket_branch_id']
        );
        $relationship->setChildRelationships(['children']);
        $results = $relationship->getEagerRelationships([[1, 2]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals('Parent ticket', $results[$key]->subject);
        $this->assertEquals(1, $results[$key]->children->count());
        $this->assertEquals('Child ticket', $results[$key]->children[0]->subject);

        $this->db->disconnect();
    }

    public function testHasManyEagerCardinalityMismatchThrows()
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // 1 FK column declared against CkOrg's 2-column composite primary key.
        $relationship = new \Pop\Db\Record\Relationships\HasMany(
            $org, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id']
        );
        $relationship->getEagerRelationships([[1]]);

        $this->db->disconnect();
    }

    public function testHasManyEagerTupleCardinalityMismatchThrows()
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // 2 FK columns, but only 1-component id tuples supplied.
        $relationship = new \Pop\Db\Record\Relationships\HasMany(
            $org, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id']
        );
        $relationship->getEagerRelationships([[1]]);

        $this->db->disconnect();
    }

    public function testHasOneEagerCardinalityMismatchThrows()
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // 1 FK column declared against CkOrg's 2-column composite primary key.
        $relationship = new \Pop\Db\Record\Relationships\HasOne(
            $org, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id']
        );
        $relationship->getEagerRelationships([[1]]);

        $this->db->disconnect();
    }

    public function testHasOneEagerTupleCardinalityMismatchThrows()
    {
        $org = new CkOrg(['org_id' => 1, 'branch_id' => 2, 'name' => 'Org A']);
        $org->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // 2 FK columns, but only 1-component id tuples supplied.
        $relationship = new \Pop\Db\Record\Relationships\HasOne(
            $org, 'Pop\Db\Test\TestAsset\CkNote', ['note_org_id', 'note_branch_id']
        );
        $relationship->getEagerRelationships([[1]]);

        $this->db->disconnect();
    }

    public function testHasOneOfLazyCardinalityMismatchThrows()
    {
        $note = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $note->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // CkNote::badOrg() declares 1 FK column against CkOrg's 2-column composite PK,
        // and the lazy (non-eager) call path must surface that just like the eager one.
        $note->badOrg();

        $this->db->disconnect();
    }

    public function testBelongsToLazyCardinalityMismatchThrows()
    {
        $note = new CkNote(['note_org_id' => 1, 'note_branch_id' => 2, 'note' => 'Note for A']);
        $note->save();

        $this->expectException(\Pop\Db\Record\Relationships\Exception::class);

        // CkNote::badOrgBelongsTo() declares 1 FK column against CkOrg's 2-column
        // composite PK, and the lazy (non-eager) call path must surface that too.
        $note->badOrgBelongsTo();

        $this->db->disconnect();
    }

    public function testFinal()
    {
        $var = 1;
        $this->assertEquals(1, $var);

        $this->db->connect();

        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();

        foreach (['ck_tickets', 'ck_notes', 'ck_orgs'] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->execute();

        $this->assertFalse($this->db->hasTable('ck_tickets'));
        $this->assertFalse($this->db->hasTable('ck_notes'));
        $this->assertFalse($this->db->hasTable('ck_orgs'));

        $this->db->disconnect();
    }

}
