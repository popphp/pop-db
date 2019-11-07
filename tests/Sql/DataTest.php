<?php

namespace Pop\Db\Test;

use Pop\Csv\Csv;
use Pop\Db\Db;
use Pop\Db\Sql;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{

    protected $password = '';

    public function testConstructor()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $data = new Sql\Data($db);
        $this->assertInstanceOf('Pop\Db\Sql\Data', $data);
    }

    public function testSerialize()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $data    = new Sql\Data($db, 'users', 10);
        $csvData = Csv::getDataFromFile(__DIR__. '/../tmp/users.csv');
        $sql     = $data->serialize($csvData, 'id', true);

        $this->assertEquals(10, $data->getDivide());
        $this->assertEquals('users', $data->getTable());
        $this->assertTrue($data->isSerialized());
        $this->assertContains('INSERT INTO `users`', $sql);
        $this->assertContains('INSERT INTO `users`', $data->getSql());
        $this->assertContains('INSERT INTO `users`', (string)$data);
    }

    public function testWriteToFile()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $data    = new Sql\Data($db, 'users', 10);
        $csvData = Csv::getDataFromFile(__DIR__. '/../tmp/users.csv');
        $data->serialize($csvData, 'id', true);
        $data->writeToFile(__DIR__ . '/../tmp/users.sql', 'START TRANSACTION;' . PHP_EOL, 'COMMIT;' . PHP_EOL);

        $this->assertFileExists(__DIR__ . '/../tmp/users.sql');
        $this->assertContains('INSERT INTO `users`', file_get_contents(__DIR__ . '/../tmp/users.sql'));
        $this->assertContains('START TRANSACTION;', file_get_contents(__DIR__ . '/../tmp/users.sql'));
        $this->assertContains('COMMIT;', file_get_contents(__DIR__ . '/../tmp/users.sql'));

        if (file_exists(__DIR__ . '/../tmp/users.sql')) {
            unlink(__DIR__ . '/../tmp/users.sql');
        }
    }

    public function testWriteToFileException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $data = new Sql\Data($db, 'users', 10);
        $data->writeToFile(__DIR__ . '/../tmp/users.sql');
    }


    public function testStreamToFile()
    {
        $db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => $this->password
        ]);
        $data    = new Sql\Data($db, 'users', 10);
        $csvData = Csv::getDataFromFile(__DIR__. '/../tmp/users.csv');
        $data->streamToFile($csvData, __DIR__ . '/../tmp/users_stream.sql', 'id', true);

        $this->assertFileExists(__DIR__ . '/../tmp/users_stream.sql');
        $this->assertContains('INSERT INTO `users`', file_get_contents(__DIR__ . '/../tmp/users_stream.sql'));

        if (file_exists(__DIR__ . '/../tmp/users_stream.sql')) {
            unlink(__DIR__ . '/../tmp/users_stream.sql');
        }
    }

}