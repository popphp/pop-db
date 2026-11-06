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
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
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

    public function testForceUpdateSerialize()
    {
        $rows = [
            [
                'id'       => 1,
                'username' => 'testuser1',
                'password' => 'password1',
                'email'    => 'testuser1@test.com'
            ],
            [
                'id'       => 2,
                'username' => 'testuser2',
                'password' => '',
                'email'    => 'testuser2@test.com'
            ],
        ];
        $data = new Data($this->db, 'pop_data_table');
        $data->setForceUpdate(true, 'id');
        $this->assertTrue($data->isForceUpdate());

        // 'username' is omitted from the SET clause; the empty (non-trailing) password
        // value becomes NULL - nullEmpty's string replacement only matches an empty
        // value that isn't the last column in the SET list
        $data->serialize($rows, 'username', true);

        $this->assertStringContainsString('UPDATE `pop_data_table` SET', $data->getSql());
        $this->assertStringContainsString('WHERE `id` = 1;', $data->getSql());
        $this->assertStringContainsString('WHERE `id` = 2;', $data->getSql());
        $this->assertStringNotContainsString('`username`', $data->getSql());
        $this->assertStringContainsString('`password` = NULL', $data->getSql());
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
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
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

    /**
     * Values that the builder interpretation of quote() would mangle, mapped to what the
     * forced (data) interpretation must write
     */
    public static function forcedValues(): array
    {
        return [
            'zip with leading zero'   => ['01234', "'01234'"],
            'fips code'               => ['001', "'001'"],
            'zero string'             => ['0', "'0'"],
            'leading colon'           => [':) thanks', "':) thanks'"],
            'question mark'           => ['?', "'?'"],
            'dollar placeholder'      => ['$1', "'$1'"],
            'backtick identifier'     => ['`x`', "'`x`'"],
            'null'                    => [null, 'NULL'],
            'empty string'            => ['', "''"],
            'int zero'                => [0, '0'],
            'negative int'            => [-7, '-7'],
            'float'                   => [1.5, '1.5'],
            'true'                    => [true, '1'],
            'false'                   => [false, '0'],
            'single quote'            => ["O'Brien", "'O\\'Brien'"],
            'newline'                 => ["line1\nline2", "'line1\\nline2'"],
            'backslash'               => ['back\\slash', "'back\\\\slash'"],
            'multibyte'               => ['café ü', "'café ü'"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forcedValues')]
    public function testForceQuoteSerializeWritesDataValues(mixed $value, string $expected)
    {
        $data = new Data($this->db, 'pop_data_table');
        $sql  = $data->serialize([['id' => 1, 'value' => $value]], null, false, true);
        $this->assertStringContainsString('(1, ' . $expected . ');', $sql);
        $this->db->disconnect();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forcedValues')]
    public function testForceQuoteStreamToFileWritesDataValues(mixed $value, string $expected)
    {
        $file = __DIR__ . '/../tmp/data.sql';
        $data = new Data($this->db, 'pop_data_table');
        $data->streamToFile([['id' => 1, 'value' => $value]], $file, null, false, null, null, true);
        $sql = file_get_contents($file);
        unlink($file);

        $this->assertStringContainsString('(1, ' . $expected . ');', $sql);
        $this->db->disconnect();
    }

    public function testForceQuoteNullEmptyIsPerValue()
    {
        $rows = [['id' => 1, 'a' => '', 'b' => null, 'c' => 'x', 'd' => '']];

        // Without nullEmpty, NULL and '' stay distinct
        $sql = new Data($this->db, 't')->serialize($rows, null, false, true);
        $this->assertStringContainsString("(1, '', NULL, 'x', '');", $sql);

        // With nullEmpty, every '' becomes NULL, including the last column
        $sql = new Data($this->db, 't')->serialize($rows, null, true, true);
        $this->assertStringContainsString("(1, NULL, NULL, 'x', NULL);", $sql);

        $this->db->disconnect();
    }

    public function testNullEmptyCoversLastColumnInForceUpdate()
    {
        $data = new Data($this->db, 'pop_data_table');
        $data->setForceUpdate(true, 'id');
        $sql = $data->serialize([['id' => 1, 'username' => 'u', 'password' => '']], null, true);

        $this->assertStringContainsString("SET `username` = 'u', `password` = NULL WHERE `id` = 1;", $sql);
        $this->db->disconnect();
    }

    public function testForceUpdateQuotesStringKey()
    {
        $data = new Data($this->db, 'pop_data_table');
        $data->setForceUpdate(true, 'code');
        $sql = $data->serialize([['code' => 'abc', 'name' => 'x']]);

        $this->assertStringContainsString("WHERE `code` = 'abc';", $sql);
        $this->db->disconnect();
    }

    /**
     * serialize() and streamToFile() must write identical statements for identical input,
     * at every divide setting and with an upsert clause
     */
    public static function divides(): array
    {
        return ['divide 0' => [0], 'divide 1' => [1], 'divide 2' => [2]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('divides')]
    public function testSerializeAndStreamToFileMatch(int $divide)
    {
        $rows = [
            ['id' => 1, 'a' => '01234', 'b' => null, 'c' => ''],
            ['id' => 2, 'a' => ':x',    'b' => 'y',  'c' => '?'],
            ['id' => 3, 'a' => "O'B",   'b' => '',   'c' => '0'],
        ];

        foreach ([[false, false], [true, false], [false, true], [true, true]] as [$nullEmpty, $forceQuote]) {
            $file = __DIR__ . '/../tmp/data.sql';

            $data = new Data($this->db, 't', $divide);
            $data->onDuplicateKeyUpdate(['a', 'b']);
            $serialized = $data->serialize($rows, null, $nullEmpty, $forceQuote);

            $data = new Data($this->db, 't', $divide);
            $data->onDuplicateKeyUpdate(['a', 'b']);
            $data->streamToFile($rows, $file, null, $nullEmpty, null, null, $forceQuote);
            $streamed = file_get_contents($file);
            unlink($file);

            $this->assertSame($serialized, $streamed);
            $this->assertEquals(
                ($divide == 0) ? 1 : (int)ceil(count($rows) / $divide),
                substr_count($streamed, 'ON DUPLICATE KEY UPDATE')
            );
        }

        $this->db->disconnect();
    }

    public function testStreamToFileAppendsBatches()
    {
        $file = __DIR__ . '/../tmp/data.sql';
        $data = new Data($this->db, 't');
        $data->streamToFile([['id' => 1]], $file, null, false, null, null, true);
        $data->streamToFile([['id' => 2]], $file, null, false, null, null, true);
        $sql = file_get_contents($file);
        unlink($file);

        $this->assertEquals(2, substr_count($sql, 'INSERT INTO'));
        $this->assertStringContainsString('(1);', $sql);
        $this->assertStringContainsString('(2);', $sql);
        $this->db->disconnect();
    }

    public function testForceQuoteRoundTripsThroughMysql()
    {
        $values = [
            1  => '01234',
            2  => '001',
            3  => '0',
            4  => ':) thanks',
            5  => '?',
            6  => '$1',
            7  => '`x`',
            8  => "O'Brien",
            9  => "line1\nline2",
            10 => 'back\\slash',
            11 => 'café ü',
            12 => '',
            13 => null,
        ];

        $this->db->query('DROP TABLE IF EXISTS pop_data_roundtrip');
        $this->db->query(
            'CREATE TABLE pop_data_roundtrip (id INT PRIMARY KEY, value VARCHAR(50) NULL) DEFAULT CHARSET=utf8mb4'
        );

        $rows = [];
        foreach ($values as $id => $value) {
            $rows[] = ['id' => $id, 'value' => $value];
        }

        $file = __DIR__ . '/../tmp/data.sql';
        new Data($this->db, 'pop_data_roundtrip', 5)->streamToFile($rows, $file, null, false, null, null, true);
        try {
            foreach (array_filter(explode(';' . PHP_EOL, file_get_contents($file)), 'trim') as $statement) {
                $this->db->query($statement);
            }
        } finally {
            unlink($file);
        }

        $this->db->query('SELECT id, value FROM pop_data_roundtrip ORDER BY id');
        $loaded = [];
        foreach ($this->db->fetchAll() as $row) {
            $loaded[(int)$row['id']] = $row['value'];
        }

        $this->db->query('DROP TABLE pop_data_roundtrip');
        $this->db->disconnect();

        $this->assertSame($values, $loaded);
    }

}
