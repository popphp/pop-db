<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema\Formatter;

use Pop\Db\Sql;
use Pop\Db\Sql\Schema\AbstractStructure;

/**
 * Schema table formatter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Table extends AbstractFormatter
{

    /**
     * Create PostgreSQL sequences
     *
     * @param  array  $increment
     * @param  string $table
     * @param  array  $columns
     * @return string
     */
    public static function createPgsqlSequences(array $increment, $table, array $columns)
    {
        $sequences = '';

        foreach ($increment as $name) {
            $sequences .= 'CREATE SEQUENCE ' . $table . '_' . $name . '_seq START ' .
                (int)$columns[$name]['increment'] . ';' . PHP_EOL;
        }

        return $sequences . PHP_EOL;
    }

    /**
     * Format primary key schema
     *
     * @param  string $dbType
     * @param  array  $primary
     * @return string
     */
    public static function formatPrimarySchema($dbType, array $primary)
    {
        $primarySchema = '';

        if ($dbType != Sql::SQLSRV) {
            $primarySchema .= ($dbType == Sql::SQLITE) ?
                ',' . PHP_EOL . '  UNIQUE (' . implode(', ', $primary) . ')' :
                ',' . PHP_EOL . '  PRIMARY KEY (' . implode(', ', $primary) . ')';
        }

        return $primarySchema;
    }

    /**
     * Format end of table
     *
     * @param  string $dbType
     * @param  string $engine
     * @param  string $charset
     * @param  int    $increment
     * @return string
     */
    public static function formatEndOfTable($dbType, $engine = null, $charset = null, $increment = null)
    {
        $sql = PHP_EOL . ')';

        if ($dbType == Sql::MYSQL) {
            if ($engine !== null) {
                $sql .= ' ENGINE=' . $engine;
            }
            if ($charset !== null) {
                $sql .= ' DEFAULT CHARSET=' . $charset;
            }
            if ($increment !== null) {
                $sql .= ' AUTO_INCREMENT=' . (int)$increment;
            }
            $sql .= ';' . PHP_EOL . PHP_EOL;
        } else {
            $sql .= ';' . PHP_EOL . PHP_EOL;
        }

        return $sql;
    }

    /**
     * Create sequences
     *
     * @param  string $dbType
     * @param  array  $increment
     * @param  string $table
     * @param  array  $columns
     * @return string
     */
    public static function createSequences($dbType, array $increment, $table, array $columns)
    {
        $sequences = '';

        if ($dbType == Sql::PGSQL) {
            foreach ($increment as $name) {
                $sequences .= 'ALTER SEQUENCE ' . self::unquoteId($table) . '_' . $name . '_seq OWNED BY ' .
                    $table . '."' . $name . '";' . PHP_EOL;
            }
            $sequences .= PHP_EOL;
        } else if ($dbType == Sql::SQLITE) {
            foreach ($increment as $name) {
                $start = (int)$columns[$name]['increment'];
                if (substr((string)$start, -1) == '1') {
                    $start -= 1;
                }
                $sequences .= 'INSERT INTO "sqlite_sequence" ("name", "seq") ' .
                    'VALUES (' . $table . ', ' . $start . ');' . PHP_EOL;
            }
            $sequences .= PHP_EOL;
        }

        return $sequences;
    }

    /**
     * Create indices
     *
     * @param  array             $indices
     * @param  string            $table
     * @param  AbstractStructure $schema
     * @return string
     */
    public static function createIndices(array $indices, $table, AbstractStructure $schema)
    {
        $indexSchema = '';

        foreach ($indices as $name => $index) {
            foreach ($index['column'] as $i => $column) {
                $index['column'][$i] = $schema->quoteId($column);
            }

            if ($index['type'] != 'primary') {
                $indexSchema .= 'CREATE ' . (($index['type'] == 'unique') ? 'UNIQUE ' : null) . 'INDEX ' .
                    $schema->quoteId($name) . ' ON ' . $schema->quoteId($table) .
                    ' (' . implode(', ', $index['column']) . ');' . PHP_EOL;
            }
        }

        return $indexSchema;
    }

    /**
     * Create constraints
     *
     * @param  array             $constraints
     * @param  string            $table
     * @param  AbstractStructure $schema
     * @return string
     */
    public static function createConstraints(array $constraints, $table, AbstractStructure $schema)
    {
        $constraintSchema = PHP_EOL;

        foreach ($constraints as $name => $constraint) {
            $constraintSchema .= 'ALTER TABLE ' . $schema->quoteId($table) .
                ' ADD CONSTRAINT ' . $schema->quoteId($name) .
                ' FOREIGN KEY (' . $schema->quoteId($constraint['column']) . ')' .
                ' REFERENCES ' . $schema->quoteId($constraint['references']) .
                ' (' . $schema->quoteId($constraint['on']) . ')' . ' ON DELETE ' .
                $constraint['delete'] . ' ON UPDATE CASCADE;' . PHP_EOL;
        }

        return $constraintSchema;
    }

}