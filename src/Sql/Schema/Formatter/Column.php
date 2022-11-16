<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
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
    public static function getColumnSchema($dbType, $name, array $column, $table)
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
    public static function getValidDataType($dbType, $type)
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
    public static function getValidMysqlDataType($type)
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
    public static function getValidPgsqlDataType($type)
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
    public static function getValidSqliteDataType($type)
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
    public static function getValidSqlsrvDataType($type)
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
    public static function formatColumn($dbType, $name, $dataType, array $column, $table)
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
    public static function formatMysqlColumn($name, $dataType, array $column)
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
    public static function formatPgsqlColumn($name, $dataType, array $column, $table)
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
    public static function formatSqliteColumn($name, $dataType, array $column)
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
    public static function formatSqlsrvColumn($name, $dataType, array $column)
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
    public static function formatCommonParameters($columnString, array $column)
    {
        if (count($column['attributes']) > 0) {
            $columnString .= ' ' . implode(' ', $column['attributes']);
        }

        if (($column['nullable'] === false) || (strtoupper((string)$column['default']) == 'NOT NULL')) {
            $columnString .= ' NOT NULL';
        }

        if ((null === $column['default']) && ($column['nullable'] === true)) {
            $columnString .= ' DEFAULT NULL';
        } else if (strtoupper((string)$column['default']) == 'NULL') {
            $columnString .= ' DEFAULT NULL';
        } else if (null !== $column['default']) {
            $columnString .= " DEFAULT '" . $column['default'] . "'";
        }

        return $columnString;
    }

}