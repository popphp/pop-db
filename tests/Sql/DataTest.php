<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => 'travis_popdb',
            'username' => 'root',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.mysql')),
            'host'     => 'localhost'
        ]);
    }

    public function testConstructor()
    {
        $data = new Data($this->db);
        $this->assertInstanceOf('Pop\Db\Sql\Data', $data);
        $this->assertEquals(1, $data->getDivide());
        $this->assertEquals('pop_db_data', $data->getTable());
        $this->db->disconnect();
    }

    public function testSerialized()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ]
        ];
        $data = new Data($this->db);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows);
        $this->assertTrue($data->isSerialized());
        $this->assertStringContainsString('INSERT INTO', $data->getSql());
        $this->db->disconnect();
    }

    public function testSerializedOmit()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ]
        ];
        $data = new Data($this->db);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows, 'email');
        $this->assertTrue($data->isSerialized());
        $this->assertStringContainsString('INSERT INTO', $data->getSql());
        $this->assertStringNotContainsString('`email`', $data->getSql());
        $this->db->disconnect();
    }

    public function testSerializedNullEmpty()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => ''
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => null
            ]
        ];
        $data = new Data($this->db);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows, null, true);
        $this->assertTrue($data->isSerialized());
        $this->assertStringContainsString('INSERT INTO', $data->getSql());
        $this->assertStringContainsString('NULL', $data->getSql());
        $this->db->disconnect();
    }

    public function testSerializedWithDivide()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $data = new Data($this->db, 'pop_data_table', 2);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows);
        $this->assertTrue($data->isSerialized());
        $this->assertEquals(3, substr_count($data->getSql(), 'INSERT INTO'));
        $this->db->disconnect();
    }

    public function testSerializedWithNoDivide()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $data = new Data($this->db, 'pop_data_table', 0);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows);
        $this->assertTrue($data->isSerialized());
        $this->assertEquals(1, substr_count($data->getSql(), 'INSERT INTO'));
        $this->db->disconnect();
    }

    public function testMysqlConflicts()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $data = new Data($this->db, 'pop_data_table');
        $data->onDuplicateKeyUpdate(['username', 'password', 'email']);
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows);
        $this->assertTrue($data->isSerialized());
        $this->assertStringContainsString(' ON DUPLICATE KEY UPDATE `username` = VALUES(username), `password` = VALUES(password), `email` = VALUES(email);', (string)$data);
        $this->db->disconnect();
    }

    public function testPgsqlConflicts()
    {
        $db = Db::pgsqlConnect([
            'database' => 'travis_popdb',
            'username' => 'postgres',
            'password' => trim(file_get_contents(__DIR__ . '/../tmp/.pgsql')),
            'host'     => 'localhost'
        ]);

        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $data = new Data($db, 'pop_data_table');
        $data->onConflict(['username', 'password', 'email'], 'id');
        $this->assertFalse($data->isSerialized());
        $data->serialize($rows);
        $this->assertTrue($data->isSerialized());
        $this->assertStringContainsString(' ON CONFLICT ("id") DO UPDATE SET "username" = excluded.username, "password" = excluded.password, "email" = excluded.email;', (string)$data);
        $db->disconnect();
    }

    public function testWriteToFile()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/data.sql');
        $data = new Data($this->db, 'pop_data_table');
        $data->serialize($rows);
        $data->writeToFile(__DIR__ . '/../tmp/data.sql');
        $this->assertFileExists(__DIR__ . '/../tmp/data.sql');
        $this->db->disconnect();

        unlink(__DIR__ . '/../tmp/data.sql');
    }

    public function testStreamToFile()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/data.sql');
        $data = new Data($this->db, 'pop_data_table');
        $data->streamToFile($rows, __DIR__ . '/../tmp/data.sql', 'password', true, '-- Sql Header', '-- Sql Footer');
        $this->assertFileExists(__DIR__ . '/../tmp/data.sql');
        $this->db->disconnect();

        unlink(__DIR__ . '/../tmp/data.sql');
    }

    public function testStreamToFileWithDivide()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/data.sql');
        $data = new Data($this->db, 'pop_data_table', 2);
        $data->streamToFile($rows, __DIR__ . '/../tmp/data.sql', 'password', true, '-- Sql Header', '-- Sql Footer');
        $this->assertFileExists(__DIR__ . '/../tmp/data.sql');
        $this->assertEquals(3, substr_count(file_get_contents(__DIR__ . '/../tmp/data.sql'), 'INSERT INTO'));
        $this->db->disconnect();

        unlink(__DIR__ . '/../tmp/data.sql');
    }

    public function testStreamToFileWithNoDivide()
    {
        $rows = [
            [
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'username' => 'testuser2',
                'password' => 'password2',
                'email'    => 'testuser2@test.com'
            ],
            [
                'username' => 'testuser3',
                'password' => 'password3',
                'email'    => 'testuser3@test.com'
            ],
            [
                'username' => 'testuser4',
                'password' => 'password4',
                'email'    => 'testuser4@test.com'
            ],
            [
                'username' => 'testuser5',
                'password' => 'password5',
                'email'    => 'testuser5@test.com'
            ]
        ];
        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/data.sql');
        $data = new Data($this->db, 'pop_data_table', 0);
        $data->streamToFile($rows, __DIR__ . '/../tmp/data.sql', 'password', true, '-- Sql Header', '-- Sql Footer');
        $this->assertFileExists(__DIR__ . '/../tmp/data.sql');
        $this->assertEquals(1, substr_count(file_get_contents(__DIR__ . '/../tmp/data.sql'), 'INSERT INTO'));
        $this->db->disconnect();

        unlink(__DIR__ . '/../tmp/data.sql');
    }

}