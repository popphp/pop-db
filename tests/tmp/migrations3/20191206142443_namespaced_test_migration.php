<?php

namespace Pop\Db\Test\TestAsset\Migrations3;

use Pop\Db\Sql\Migration\AbstractMigration;

class NamespacedTestMigration extends AbstractMigration
{

    public function up(): void
    {
        $schema = $this->db->createSchema();
        $schema->create('test_users_ns')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->primary('id');

        $this->db->query($schema);
    }

    public function down(): void
    {
        $schema = $this->db->createSchema();
        $schema->drop('test_users_ns');
        $this->db->query($schema);
    }

}
