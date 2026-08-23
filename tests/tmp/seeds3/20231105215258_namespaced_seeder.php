<?php

namespace Pop\Db\Test\TestAsset\Seeds3;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class NamespacedSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {
        $schema = $db->createSchema();
        $schema->create('namespaced_users')
            ->int('id', 16)->notNullable()->increment()
            ->varchar('username', 255)->notNullable()
            ->primary('id');

        $db->query($schema);
    }

}
