<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema\Formatter;

use Pop\Db\Sql;

/**
 * Schema column formatter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Column extends AbstractFormatter
{

    /**
     * Get column schema
     *
     * @param  string $dbType
     * @param  string $name
     * @param  array  $column
     * @param  string $table
     * @throws Exception
     * @return string
     */
    public static function getColumnSchema(string $dbType, string $name, array $column, string $table): string
    {
        if (!isset($column['type'])) {
            throw new Exception('Error: The column type was not set.');
        }

        $dataType = self::getValidDataType($dbType, $column['type']);

        return self::formatColumn($dbType, $name, $dataType, $column, $table);
    }

    /**
     * Get valid column data type
     *
     * @param  string $dbType
     * @param  string $type
     * @return string
     */
    public static function getValidDataType(string $dbType, string $type): string
    {
        switch ($dbType) {
            case Sql::MYSQL:
                return self::getValidMysqlDataType($type);
                break;
            case Sql::PGSQL:
                return self::getValidPgsqlDataType($type);
                break;
            case Sql::SQLITE:
                return self::getValidSqliteDataType($type);
                break;
            case Sql::SQLSRV:
                return self::getValidSqlsrvDataType($type);
                break;
            default:
                return $type;
        }
    }

    /**
     * Get valid MySQL data type
     *
     * @param  string $type
     * @return string
     */
    public static function getValidMysqlDataType(string $type): string
    {
        $type = strtoupper($type);

        switch ($type) {
            case 'INTEGER':
                $type = 'INT';
                break;
            case 'SERIAL':
                $type = 'INT';
                break;
            case 'BIGSERIAL':
                $type = 'BIGINT';
                break;
            case 'SMALLSERIAL':
                $type = 'SMALLINT';
                break;
            case 'CHARACTER VARYING':
                $type = 'VARCHAR';
                break;
            case 'CHARACTER':
                $type = 'CHAR';
                break;
        }

        return $type;
    }

    /**
     * Get valid PostgreSQL data type
     *
     * @param  string $type
     * @return string
     */
    public static function getValidPgsqlDataType(string $type): string
    {
        $type = strtoupper($type);

        switch ($type) {
            case 'INT':
            case 'MEDIUMINT':
                $type = 'INTEGER';
                break;
            case 'TINYINT':
                $type = 'SMALLINT';
                break;
                break;
            case 'DOUBLE':
                $type = 'DOUBLE PRECISION';
                break;
            case 'BLOB':
            case 'TINYBLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
            case 'TINYTEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                $type = 'TEXT';
                break;
            case 'BINARY':
            case 'VARBINARY':
                $type = 'BYTEA';
                break;
            case 'DATETIME':
                $type = 'TIMESTAMP';
                break;
        }

        return $type;
    }

    /**
     * Get valid SQLite data type
     *
     * @param  string $type
     * @return string
     */
    public static function getValidSqliteDataType(string $type): string
    {
        $type = strtoupper($type);

        switch ($type) {
            case 'INT':
            case 'SMALLINT':
            case 'TINYINT':
            case 'MEDIUMINT':
            case 'BIGINT':
            case 'SERIAL':
            case 'BIGSERIAL':
            case 'SMALLSERIAL':
                $type = 'INTEGER';
                break;
            case 'FLOAT':
            case 'DOUBLE':
            case 'DOUBLE PRECISION':
                $type = 'REAL';
                break;
            case 'DECIMAL':
                $type = 'NUMERIC';
                break;
            case 'TINYBLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
                $type = 'BLOB';
                break;
            case 'TINYTEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                $type = 'TEXT';
                break;
            case 'TIMESTAMP':
                $type = 'DATETIME';
                break;
        }

        return $type;
    }

    /**
     * Get valid SQL Server data type
     *
     * @param  string $type
     * @return string
     */
    public static function getValidSqlsrvDataType(string $type): string
    {
        $type = strtoupper($type);

        switch ($type) {
            case 'INTEGER':
            case 'MEDIUMINT':
            case 'SERIAL':
                $type = 'INT';
                break;
            case 'BIGSERIAL':
                $type = 'BIGINT';
                break;
            case 'SMALLSERIAL':
                $type = 'SMALLINT';
                break;
            case 'DOUBLE':
            case 'DOUBLE PRECISION':
                $type = 'REAL';
                break;
            case 'BLOB':
            case 'TINYBLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
            case 'TINYTEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                $type = 'TEXT';
                break;
            case 'TIMESTAMP':
                $type = 'DATETIME';
                break;
        }

        return $type;
    }

    /**
     * Format column
     *
     * @param  string $dbType
     * @param  string $name
     * @param  string $dataType
     * @param  array  $column
     * @param  string $table
     * @throws Exception
     * @return string
     */
    public static function formatColumn(string $dbType, string $name, string $dataType, array $column, string $table): string
    {
        switch ($dbType) {
            case Sql::MYSQL:
                return self::formatMysqlColumn($name, $dataType, $column);
                break;
            case Sql::PGSQL:
                return self::formatPgsqlColumn($name, $dataType, $column, $table);
                break;
            case Sql::SQLITE:
                return self::formatSqliteColumn($name, $dataType, $column);
                break;
            case Sql::SQLSRV:
                return self::formatSqlsrvColumn($name, $dataType, $column);
                break;
            default:
                throw new Exception("Error: The database type '" . $dbType . "' is not supported.");
        }
    }

    /**
     * Format MySQL column
     *
     * @param  string $name
     * @param  string $dataType
     * @param  array  $column
     * @return string
     */
    public static function formatMysqlColumn(string $name, string $dataType, array $column): string
    {
        $columnString = $name . ' ' . $dataType;
        $sizeAllowed  = ['DECIMAL', 'NUMERIC', 'FLOAT', 'DOUBLE', 'REAL', 'DOUBLE PRECISION'];

        if (!empty($column['size']) &&
            ((stripos($dataType, 'INT') !== false) || (stripos($dataType, 'CHAR') !== false) ||
                (stripos($dataType, 'BINARY') !== false) || in_array($dataType, $sizeAllowed))) {
            $columnString .= '(' . $column['size'];
            $columnString .= (!empty($column['precision']) && in_array($dataType, $sizeAllowed)) ?
                ', ' . $column['precision'] . ')' : ')';
        }

        if ($column['unsigned'] !== false) {
            $columnString .= ' UNSIGNED';
        }

        $columnString = self::formatCommonParameters($columnString, $column);

        if ($column['increment'] !== false) {
            $columnString .= ' AUTO_INCREMENT';
        }

        return $columnString;
    }

    /**
     * Format PostgreSQL column
     *
     * @param  string $name
     * @param  string $dataType
     * @param  array  $column
     * @param  string $table
     * @return string
     */
    public static function formatPgsqlColumn(string $name, string $dataType, array $column, string $table): string
    {
        $columnString     = $name . ' ' . $dataType;
        $unquotedName     = self::unquoteId($name);
        $sizeAllowed      = ['DECIMAL', 'NUMERIC', 'FLOAT', 'REAL'];
        $precisionAllowed = ['DECIMAL', 'NUMERIC'];

        if (!empty($column['size']) &&
            ((stripos($dataType, 'CHAR') !== false) || in_array($dataType, $sizeAllowed))) {
            $columnString .= '(' . $column['size'];
            $columnString .= (!empty($column['precision']) && in_array($dataType, $precisionAllowed)) ?
                ', ' . $column['precision'] . ')' : ')';
        }

        $columnString = self::formatCommonParameters($columnString, $column);

        if ($column['increment'] !== false) {
            $columnString .= ' DEFAULT nextval(\'' . $table . '_' . $unquotedName . '_seq\')';
        }

        return $columnString;
    }

    /**
     * Format SQLite column
     *
     * @param  string $name
     * @param  string $dataType
     * @param  array  $column
     * @return string
     */
    public static function formatSqliteColumn(string $name, string $dataType, array $column): string
    {
        $columnString = $name . ' ' . $dataType;
        $columnString = self::formatCommonParameters($columnString, $column);

        if ($column['increment'] !== false) {
            $columnString .= (($column['primary'] !== false) ? ' PRIMARY KEY' : null) . ' AUTOINCREMENT';
        }

        return $columnString;
    }

    /**
     * Format SQL Server column
     *
     * @param  string $name
     * @param  string $dataType
     * @param  array  $column
     * @return string
     */
    public static function formatSqlsrvColumn(string $name, string $dataType, array $column): string
    {
        $columnString = $name . ' ' . $dataType;
        $sizeAllowed      = ['DECIMAL', 'NUMERIC', 'FLOAT', 'REAL'];
        $precisionAllowed = ['DECIMAL', 'NUMERIC'];

        if (!empty($column['size']) &&
            ((stripos($dataType, 'CHAR') !== false) || (stripos($dataType, 'BINARY') !== false) ||
              in_array($dataType, $sizeAllowed))) {
            $columnString .= '(' . $column['size'];
            $columnString .= (!empty($column['precision']) && in_array($dataType, $precisionAllowed)) ?
                ', ' . $column['precision'] . ')' : ')';
        }

        $columnString = self::formatCommonParameters($columnString, $column);

        if ($column['increment'] !== false) {
            $columnString .= (($column['primary'] !== false) ? ' PRIMARY KEY' : null) .
                ' IDENTITY(' . (int)$column['increment'] . ', 1)';
        }

        return $columnString;
    }

    /**
     * Format common column parameters
     *
     * @param  string $columnString
     * @param  array  $column
     * @return string
     */
    public static function formatCommonParameters(string $columnString, array $column): string
    {
        if (count($column['attributes']) > 0) {
            $columnString .= ' ' . implode(' ', $column['attributes']);
        }

        if (($column['nullable'] === false) || (strtoupper((string)$column['default']) == 'NOT NULL')) {
            $columnString .= ' NOT NULL';
        }

        if (($column['default'] === null) && ($column['nullable'] === true)) {
            $columnString .= ' DEFAULT NULL';
        } else if (strtoupper((string)$column['default']) == 'NULL') {
            $columnString .= ' DEFAULT NULL';
        } else if ($column['default'] !== null) {
            $columnString .= " DEFAULT " . ((!Sql::isSupportedFunction($column['default'])) ?
                    "'" . $column['default'] . "'" : $column['default']);
        }

        return $columnString;
    }

}
