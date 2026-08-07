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

}
