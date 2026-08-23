<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class FreshIncludeTestMigration extends AbstractMigration
{

    public function up(): void
    {
        $schema = $this->db->createSchema();
        $schema->create('test_users_fresh')
            ->int('id', 16)
            ->varchar('username', 255)
            ->primary('id');

        $this->db->query($schema);
    }

    public function down(): void
    {
        $schema = $this->db->createSchema();
        $schema->drop('test_users_fresh');
        $this->db->query($schema);
    }

}
