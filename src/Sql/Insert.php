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
namespace Pop\Db\Sql;

/**
 * Insert class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Insert extends AbstractClause
{

    /**
     * Conflict key for UPSERT
     * @var ?string
     */
    protected ?string $conflictKey = null;

    /**
     * Conflict columns for UPSERT
     * @var array
     */
    protected array $conflictColumns = [];

    /**
     * Set into table
     *
     * @param  mixed $table
     * @return Insert
     */
    public function into(mixed $table): Insert
    {
        $this->setTable($table);
        return $this;
    }

    /**
     * Set a value
     *
     * @param  array $values
     * @return Insert
     */
    public function values(array $values): Insert
    {
        $this->setValues($values);
        return $this;
    }

    /**
     * Set what to do on a insert conflict (UPSERT - PostgreSQL & SQLite)
     *
     * @param  array   $columns
     * @param  ?string $key
     * @return Insert
     */
    public function onConflict(array $columns, ?string $key = null): Insert
    {
        $this->conflictColumns = $columns;
        $this->conflictKey     = $key;
        return $this;
    }

    /**
     * Set columns to handle duplicates/conflicts (UPSERT - MySQL-ism)
     *
     * @param  array $columns
     * @return Insert
     */
    public function onDuplicateKeyUpdate(array $columns): Insert
    {
        $this->onConflict($columns);
        return $this;
    }

    /**
     * Render the INSERT statement
     *
     * @return string
     */
    public function render(): string
    {
        // Start building the INSERT statement
        $sql     = 'INSERT INTO ' . $this->quoteId($this->table) . ' ';
        $columns = [];
        $values  = [];

        $paramCount = 1;
        $dbType     = $this->getDbType();

        foreach ($this->values as $column => $value) {
            $colValue = (strpos($column, '.') !== false) ?
                substr($column, (strpos($column, '.') + 1)) : $column;

            $columns[] = $this->quoteId($column);
            $values[]  = ($value === null) ? 'NULL' : $this->quote($value);
        }

        $sql .= '(' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';

        // Handle conflicts/duplicates (UPSERT)
        if (!empty($this->conflictColumns)) {
            $updates = [];
            switch ($dbType) {
                case self::MYSQL:
                    foreach ($this->conflictColumns as $conflictColumn) {
                        $updates[] = $this->quoteId($conflictColumn) . ' = VALUES(' . $conflictColumn .')';
                    }
                    $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
                    break;
                case self::SQLITE:
                case self::PGSQL:
                    foreach ($this->conflictColumns as $conflictColumn) {
                        $updates[] = $this->quoteId($conflictColumn) . ' = excluded.' . $conflictColumn;
                    }
                    $sql .= ' ON CONFLICT (' . $this->quoteId($this->conflictKey) . ') DO UPDATE SET '
                        . implode(', ', $updates);
                    break;
            }
        }

        return $sql;
    }

    /**
     * Render the INSERT statement
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
