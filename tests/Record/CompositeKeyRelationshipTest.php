<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Db;
use Pop\Db\Test\TestAsset\CkOrg;
use Pop\Db\Test\TestAsset\CkNote;
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
        foreach (['ck_notes', 'ck_orgs'] as $table) {
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
            ->int('org_id', 16)
            ->int('branch_id', 16)
            ->varchar('note', 255)
            ->primary('id');
        $schema->execute();
        $this->db->disconnect();
        $this->db->connect();

        CkOrg::setDb($this->db);
        CkNote::setDb($this->db);
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

        $noteA = new CkNote(['org_id' => 1, 'branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['org_id' => 2, 'branch_id' => 1, 'note' => 'Note for B']);
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

        $noteA = new CkNote(['org_id' => 1, 'branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['org_id' => 2, 'branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasMany($orgA, 'Pop\Db\Test\TestAsset\CkNote', ['org_id', 'branch_id']);
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

        $noteA = new CkNote(['org_id' => 1, 'branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['org_id' => 2, 'branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasMany(
            $orgA,
            'Pop\Db\Test\TestAsset\CkNote',
            ['org_id', 'branch_id'],
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

        $noteA = new CkNote(['org_id' => 1, 'branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['org_id' => 2, 'branch_id' => 1, 'note' => 'Note for B']);
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

        $noteA = new CkNote(['org_id' => 1, 'branch_id' => 2, 'note' => 'Note for A']);
        $noteA->save();
        $noteB = new CkNote(['org_id' => 2, 'branch_id' => 1, 'note' => 'Note for B']);
        $noteB->save();

        $relationship = new \Pop\Db\Record\Relationships\HasOne($orgA, 'Pop\Db\Test\TestAsset\CkNote', ['org_id', 'branch_id']);
        $results      = $relationship->getEagerRelationships([[1, 2], [2, 1]]);

        $key = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [1, 2]);
        $this->assertEquals('Note for A', $results[$key]->note);

        $otherKey = implode(\Pop\Db\Record\Relationships\AbstractRelationship::COMPOSITE_KEY_DELIMITER, [2, 1]);
        $this->assertEquals('Note for B', $results[$otherKey]->note);

        $this->db->disconnect();
    }

    public function testFinal()
    {
        $var = 1;
        $this->assertEquals(1, $var);

        $this->db->connect();

        $schema = $this->db->createSchema();
        $schema->disableForeignKeyCheck();

        foreach (['ck_notes', 'ck_orgs'] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->execute();

        $this->assertFalse($this->db->hasTable('ck_notes'));
        $this->assertFalse($this->db->hasTable('ck_orgs'));

        $this->db->disconnect();
    }

}
