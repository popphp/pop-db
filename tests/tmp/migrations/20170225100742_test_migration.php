<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class TestMigration extends AbstractMigration
{

    public function up()
    {
        $schema = $this->db->createSchema();
        $schema->create('test_users')
            ->int('id', 16)
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->primary('id');

        $this->db->query($schema);
    }

    public function down()
    {
        $schema = $this->db->createSchema();
        $schema->drop('test_users');
        $this->db->query($schema);
    }

}