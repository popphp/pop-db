<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use Pop\Db\Sql\Schema\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{

    public function testUnquoteId()
    {
        $this->assertEquals('users.id', Formatter\Column::unquoteId('`users`.`id`'));
    }

    public function testGetColumnSchemaException()
    {
        $this->expectException('Pop\Db\Sql\Schema\Formatter\Exception');
        Formatter\Column::getColumnSchema('MYSQL', 'id', [], 'users');
    }

    public function testDataType()
    {
        $this->assertEquals('CHAR', Formatter\Column::getValidDataType('SQL', 'CHAR'));
    }

    public function testMysqlDataType()
    {
        $this->assertEquals('VARCHAR', Formatter\Column::getValidDataType('MYSQL', 'CHARACTER VARYING'));
        $this->assertEquals('CHAR', Formatter\Column::getValidDataType('MYSQL', 'CHARACTER'));
    }

    public function testSqlsrvDataType()
    {
        $this->assertEquals('INT', Formatter\Column::getValidDataType('SQLSRV', 'INTEGER'));
        $this->assertEquals('INT', Formatter\Column::getValidDataType('SQLSRV', 'MEDIUMINT'));
        $this->assertEquals('INT', Formatter\Column::getValidDataType('SQLSRV', 'SERIAL'));
        $this->assertEquals('BIGINT', Formatter\Column::getValidDataType('SQLSRV', 'BIGSERIAL'));
        $this->assertEquals('SMALLINT', Formatter\Column::getValidDataType('SQLSRV', 'SMALLSERIAL'));
        $this->assertEquals('REAL', Formatter\Column::getValidDataType('SQLSRV', 'DOUBLE'));
        $this->assertEquals('REAL', Formatter\Column::getValidDataType('SQLSRV', 'DOUBLE PRECISION'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'BLOB'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'TINYBLOB'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'MEDIUMBLOB'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'LONGBLOB'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'TINYTEXT'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'MEDIUMTEXT'));
        $this->assertEquals('TEXT', Formatter\Column::getValidDataType('SQLSRV', 'LONGTEXT'));
        $this->assertEquals('DATETIME', Formatter\Column::getValidDataType('SQLSRV', 'TIMESTAMP'));
    }

    public function testFormatColumnException()
    {
        $this->expectException('Pop\Db\Sql\Schema\Formatter\Exception');
        $column = [
            'type'       => 'INT',
            'precision'  => null,
            'nullable'   => null,
            'default'    => null,
            'increment'  => false,
            'primary'    => false,
            'unsigned'   => false,
            'attributes' => []
        ];
        $this->assertEquals('[id] INT', Formatter\Column::formatColumn('BADSQL', '[id]', 'INT', $column, '[users]'));
    }

    public function testSqlsrvFormatColumn()
    {
        $column = [
            'type'       => 'DECIMAL',
            'size'       => 16,
            'precision'  => 2,
            'nullable'   => null,
            'default'    => null,
            'increment'  => false,
            'primary'    => false,
            'unsigned'   => false,
            'attributes' => []
        ];
        $this->assertEquals('[price] DECIMAL(16, 2)', Formatter\Column::formatColumn('SQLSRV', '[price]', 'DECIMAL', $column, '[users]'));
    }

    public function testSqlsrvFormatColumnIncrement()
    {
        $column = [
            'type'       => 'INT',
            'size'       => null,
            'precision'  => null,
            'nullable'   => null,
            'default'    => null,
            'increment'  => true,
            'primary'    => true,
            'unsigned'   => false,
            'attributes' => []
        ];
        $this->assertEquals('[id] INT PRIMARY KEY IDENTITY(1, 1)', Formatter\Column::formatColumn('SQLSRV', '[id]', 'INT', $column, '[users]'));
    }

    public function testFormatColumnAttributes()
    {
        $column = [
            'type'       => 'VARCHAR',
            'size'       => 255,
            'precision'  => null,
            'nullable'   => null,
            'default'    => null,
            'increment'  => false,
            'primary'    => false,
            'unsigned'   => false,
            'attributes' => [
                'CHARACTER SET utf8',
                'COLLATE utf8_bin'
            ]
        ];
        $this->assertEquals('`username` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin', Formatter\Column::formatColumn('MYSQL', '`username`', 'VARCHAR', $column, '`users`'));
    }

}