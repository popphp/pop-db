<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {
        $schema = $db->createSchema();
        $schema->create('users')
            ->int('id', 16)->notNullable()->increment()
            ->varchar('username', 255)->notNullable()
            ->varchar('password', 255)->notNullable()
            ->varchar('email', 255)->nullable()
            ->primary('id');

        $db->query($schema);
    }

}