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
