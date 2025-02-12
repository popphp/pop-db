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
namespace Pop\Db\Sql\Schema;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Schema ALTER table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class Alter extends AbstractStructure
{

    /**
     * Existing columns in the table
     * @var array
     */
    protected array $existingColumns = [];

    /**
     * Columns to be dropped
     * @var array
     */
    protected array $dropColumns = [];

    /**
     * Indices to be dropped
     * @var array
     */
    protected array $dropIndices = [];

    /**
     * Constraints to be dropped
     * @var array
     */
    protected array $dropConstraints = [];

    /**
     * Constructor
     *
     * Instantiate the ALTER table object
     *
     * @param  string          $table
     * @param  AbstractAdapter $db
     */
    public function __construct(string $table, $db)
    {
        parent::__construct($table, $db);

        if (count($this->info['columns']) > 0) {
            foreach ($this->info['columns'] as $name => $column) {
                $size      = null;
                $precision = null;
                if (str_contains($column['type'], '(')) {
                    $type = substr($column['type'], 0, strpos($column['type'], '('));
                    if (str_contains($column['type'], ',')) {
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
     * @param  string  $oldName
     * @param  string  $newName
     * @param  ?string $type
     * @param  mixed   $size
     * @param  mixed   $precision
     * @return Alter
     */
    public function modifyColumn(string $oldName, string $newName, ?string $type = null, mixed $size = null, mixed $precision = null): Alter
    {
        if (isset($this->existingColumns[$oldName])) {
            if ($type !== null) {
                $this->existingColumns[$oldName]['type'] = $type;
            }
            if ($size !== null) {
                $this->existingColumns[$oldName]['size'] = $size;
            }
            if ($precision !== null) {
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
    public function dropColumn(string $name): Alter
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
    public function dropIndex(string $name): Alter
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
    public function dropConstraint(string $name): Alter
    {
        if (!in_array($name, $this->dropConstraints)) {
            $this->dropConstraints[] = $name;
        }
        return $this;
    }

    /**
     * Set the AFTER column (MySQL-only))
     *
     * @param  string $column
     * @return Alter
     */
    public function after(string $column): Alter
    {
        if (($this->currentColumn !== null) && isset($this->columns[$this->currentColumn])) {
            $this->columns[$this->currentColumn]['after'] = $column;
        }
        return $this;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render(): string
    {
        $schema = '';

        // Modify existing columns
        foreach ($this->existingColumns as $name => $column) {
            if ($column['modify'] !== null) {
                if ($this->isMysql()) {
                    $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) .
                        ' CHANGE COLUMN ' . $this->quoteId($name) . ' ' .
                        $this->getColumnSchema($column['modify'], $column) . ';' . PHP_EOL;
                } else {
                    if ($column['modify'] == $name) {
                        $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' ALTER COLUMN ' .
                            $this->getColumnSchema($column['modify'], $column) . ';' . PHP_EOL;
                    } else {
                        $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' RENAME COLUMN ' .
                            $this->quoteId($name) . ' ' . $this->quoteId($column['modify']) . ';' . PHP_EOL;
                    }
                }
            }
        }

        // Add new columns
        foreach ($this->columns as $name => $column) {
            $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' ADD ' . $this->getColumnSchema($name, $column);
            if (($this->isMysql()) && !empty($column['after'])) {
                $schema .= ' AFTER ' . $this->quoteId($column['after']);
            }
            $schema .= ';' . PHP_EOL;
        }

        // Drop columns
        foreach ($this->dropColumns as $name => $column) {
            $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP COLUMN ' . $this->quoteId($column) . ';' . PHP_EOL;
        }

        // Drop indices
        foreach ($this->dropIndices as $index) {
            if ($this->isMysql()) {
                $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP INDEX ' . $this->quoteId($index) . ';' . PHP_EOL;
            } else {
                $schema .= 'DROP INDEX ' . $this->quoteId($this->table . '.' . $index) . ';' . PHP_EOL;
            }
        }

        // Drop constraints
        foreach ($this->dropConstraints as $constraint) {
            if ($this->isMysql()) {
                $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP FOREIGN KEY ' .
                    $this->quoteId($constraint) . ';' . PHP_EOL;
            } else {
                $schema .= 'ALTER TABLE ' . $this->quoteId($this->table) . ' DROP CONSTRAINT ' .
                    $this->quoteId($constraint) . ';' . PHP_EOL;
            }
        }

        // Add indices
        if (count($this->indices) > 0) {
            $schema .= Formatter\Table::createIndices($this->indices, $this->table, $this);
        }

        // Add constraints
        if (count($this->constraints) > 0) {
            $schema .= Formatter\Table::createConstraints($this->constraints, $this->table, $this);
        }

        return $schema . PHP_EOL;
    }

    /**
     * Render the table schema to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
