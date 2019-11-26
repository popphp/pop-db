<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Insert extends AbstractClause
{

    /**
     * Update columns
     * @var array
     */
    protected $updateColumns = [];

    /**
     * Set into table
     *
     * @param  mixed  $table
     * @return Insert
     */
    public function into($table)
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
    public function values(array $values)
    {
        $this->setValues($values);
        return $this;
    }

    /**
     * Set update columns
     *
     * @param  array $updateColumns
     * @return Insert
     */
    public function onDuplicateKeyUpdate(array $updateColumns)
    {
        $this->updateColumns = $updateColumns;
        return $this;
    }

    /**
     * Render the INSERT statement
     *
     * @return string
     */
    public function render()
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

            // Check for named parameters
            if ((':' . $colValue == substr($value, 0, strlen(':' . $colValue))) && ($dbType !== self::SQLITE)) {
                if (($dbType == self::MYSQL) || ($dbType == self::SQLSRV)) {
                    $value = '?';
                } else if (($dbType == self::PGSQL) && !($this->db instanceof \Pop\Db\Adapter\Pdo)) {
                    $value = '$' . $paramCount;
                    $paramCount++;
                }
            }
            $columns[] = $this->quoteId($column);
            $values[]  = (null === $value) ? 'NULL' : $this->quote($value);
        }

        $sql .= '(' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';

        if (!empty($this->updateColumns)) {
            $updates = [];
            foreach ($this->updateColumns as $updateColumn) {
                $updates[] = $this->quoteId($updateColumn) . ' = VALUES(' . $updateColumn .')';
            }

            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        }

        return $sql;
    }

    /**
     * Render the INSERT statement
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}