<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Schema ALTER table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Alter extends AbstractStructure
{

    /**
     * Existing columns in the table
     * @var array
     */
    protected $existingColumns = [];

    /**
     * Columns to be dropped
     * @var array
     */
    protected $dropColumns = [];

    /**
     * Indices to be dropped
     * @var array
     */
    protected $dropIndices = [];

    /**
     * Constraints to be dropped
     * @var array
     */
    protected $dropConstraints = [];

    /**
     * Constructor
     *
     * Instantiate the ALTER table object
     *
     * @param  string          $table
     * @param  AbstractAdapter $db
     */
    public function __construct($table, $db)
    {
        parent::__construct($table, $db);

        if (count($this->info['columns']) > 0) {
            foreach ($this->info['columns'] as $name => $column) {
                $size      = null;
                $precision = null;
                if (strpos($column['type'], '(') !== false) {
                    $type = substr($column['type'], 0, strpos($column['type'], '('));
                    if (strpos($column['type'], ',') !== false) {
                        $size = substr($column['type'], (strpos($column['type'], '(') + 1));
                        $size = substr($size, 0, strpos($size, ','));
                        $precision = substr($column['type'], (strpos($column['type'], ',') + 1));
                        $precision = trim(substr($precision, 0, strpos($precision, ')')));
                    } else {
                        $size = substr($column['type'], (strpos($column['type'], '(') + 1));
                        $size = substr($size, 0, strpos($size, ')'));
                    }
                } else {
                    $type = $column['type'];
                }

                $this->existingColumns[$name] = [
                    'type'       => $type,
                    'size'       => $size,
                    'precision'  => $precision,
                    'nullable'   => $column['null'],
                    'default'    => null,
                    'increment'  => false,
                    'primary'    => $column['primary'],
                    'unsigned'   => false,
                    'attributes' => [],
                    'modify'     => null
                ];
            }
        }
    }

    /**
     * Modify a column
     *
     * @param  string $oldName
     * @param  string $newName
     * @param  string $type
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return Alter
     */
    public function modifyColumn($oldName, $newName, $type = null, $size = null, $precision = null)
    {
        if (isset($this->existingColumns[$oldName])) {
            if (null !== $type) {
                $this->existingColumns[$oldName]['type'] = $type;
            }
            if (null !== $size) {
                $this->existingColumns[$oldName]['size'] = $size;
            }
            if (null !== $precision) {
                $this->existingColumns[$oldName]['precision'] = $precision;
            }

            $this->existingColumns[$oldName]['modify'] = $newName;
        }

        return $this;
    }

    /**
     * Drop a column
     *
     * @param  string $name
     * @return Alter
     */
    public function dropColumn($name)
    {
        if (!in_array($name, $this->dropColumns)) {
            $this->dropColumns[] = $name;
        }
        return $this;
    }

    /**
     * Drop an index
     *
     * @param  string $name
     * @return Alter
     */
    public function dropIndex($name)
    {
        if (!in_array($name, $this->dropIndices)) {
            $this->dropIndices[] = $name;
        }
        return $this;
    }

    /**
     * Drop a constraint
     *
     * @param  string $name
     * @return Alter
     */
    public function dropConstraint($name)
    {
        if (!in_array($name, $this->dropConstraints)) {
            $this->dropConstraints[] = $name;
        }
        return $this;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render()
    {
        $sql = '';

        // Modify existing columns
        foreach ($this->existingColumns as $name => $column) {
            if (null !== $column['modify']) {
                if ($this->isMysql()) {
                    $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) .
                        ' CHANGE COLUMN ' . $this->quoteId($name) . ' ' .
                        $this->quoteId($column['modify']) . ' ' . $this->getColumnType($column['modify'], $column) . ';' . PHP_EOL;
                } else {
                    if ($column['modify'] == $name) {
                        $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' ALTER COLUMN ' .
                            $this->quoteId($name) . ' ' . $this->getColumnType($column['modify'], $column) . ';' . PHP_EOL;
                    } else {
                        $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' RENAME COLUMN ' .
                            $this->quoteId($name) . ' ' . $this->quoteId($column['modify']) . ';' . PHP_EOL;
                    }
                }
            }
        }

        // Add new columns
        foreach ($this->columns as $name => $column) {
            $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' ADD ' .
                $this->quoteId($name) . ' ' . $this->getColumnType($name, $column) . ';' . PHP_EOL;
        }

        // Drop columns
        foreach ($this->dropColumns as $name => $column) {
            $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP COLUMN ' . $this->quoteId($column) . ';' . PHP_EOL;
        }

        // Drop indices
        foreach ($this->dropIndices as $index) {
            if ($this->isMysql()) {
                $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP INDEX ' . $this->quoteId($index) . ';' . PHP_EOL;
            } else {
                $sql .= 'DROP INDEX ' . $this->quoteId($this->table . '.' . $index) . ';' . PHP_EOL;
            }
        }

        // Drop constraints
        foreach ($this->dropConstraints as $constraint) {
            if ($this->isMysql()) {
                $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP FOREIGN KEY ' .
                    $this->quoteId($constraint) . ';' . PHP_EOL;
            } else {
                $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP CONSTRAINT ' .
                    $this->quoteId($constraint) . ';' . PHP_EOL;
            }
        }

        // Add indices
        if (count($this->indices) > 0) {
            $sql .= PHP_EOL;
            foreach ($this->indices as $name => $index) {
                foreach ($index['column'] as $i => $column) {
                    $index['column'][$i] = $this->quoteId($column);
                }

                if ($index['type'] != 'primary') {
                    $sql .= 'CREATE ' . (($index['type'] == 'unique') ? 'UNIQUE ' : null) . 'INDEX ' . $this->quoteId($name) .
                        ' ON ' . $this->quoteId($this->table) . ' (' . implode(', ', $index['column']) . ');' . PHP_EOL;
                }
            }
        }

        // Add constraints
        if (count($this->constraints) > 0) {
            $sql .= PHP_EOL;
            foreach ($this->constraints as $name => $constraint) {
                $sql .= 'ALTER TABLE ' . $this->quoteId($this->table) .
                    ' ADD CONSTRAINT ' . $this->quoteId($name) .
                    ' FOREIGN KEY (' . $this->quoteId($constraint['column']) . ')' .
                    ' REFERENCES ' . $this->quoteId($constraint['references']) . ' (' . $this->quoteId($constraint['on']) . ')' .
                    ' ON DELETE ' . $constraint['delete'] . ' ON UPDATE CASCADE;' . PHP_EOL;
            }
        }

        return $sql . PHP_EOL;
    }

    /**
     * Render the table schema to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}