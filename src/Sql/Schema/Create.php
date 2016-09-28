<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema;

/**
 * Schema CREATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Create extends AbstractStructure
{

    protected $ifNotExists = false;

    public function ifNotExists()
    {
        $this->ifNotExists = true;
        return $this;
    }

    public function render()
    {
        $sql = '';

        // Create PGSQL sequence
        if ($this->hasIncrement()) {
            $increment = $this->getIncrement();
            if ($this->dbType == self::PGSQL) {
                foreach ($increment as $name) {
                    $sql .= 'CREATE SEQUENCE ' . $this->table . '_' . $name . '_seq START ' . (int)$this->columns[$name]['increment'] . ';';
                }
                $sql .= PHP_EOL . PHP_EOL;
            }
        }

        /*
         * BEGIN CREATE TABLE
         */
        $sql .= 'CREATE TABLE ' . ((($this->ifNotExists) && ($this->dbType != self::SQLSRV)) ? 'IF NOT EXISTS ' : null) .
            $this->quoteId($this->table) . ' (' . PHP_EOL;

        $i = 0;
        foreach ($this->columns as $name => $column) {
            $sql .= (($i != 0) ? ',' . PHP_EOL : null) . '  ' . $this->quoteId($name) . ' ' . $this->getColumnType($column);
            $i++;
        }

        if ($this->hasPrimary()) {
            $sql .= ',' . PHP_EOL . '  PRIMARY KEY (' . implode(', ', $this->getPrimary(true)) . ')';
        }

        $sql .= PHP_EOL . ');' . PHP_EOL . PHP_EOL;

        /*
         * END CREATE TABLE
         */

        // Assign PGSQL or SQLITE sequences
        if ($this->hasIncrement()) {
            $increment = $this->getIncrement();
            if ($this->dbType == self::PGSQL) {
                foreach ($increment as $name) {
                    $sql .= 'ALTER SEQUENCE ' . $this->table . '_' . $name . '_seq OWNED BY ' . $this->quoteId($this->table . '.' . $name) . ';' . PHP_EOL;
                }
                $sql .= PHP_EOL;
            } else if ($this->dbType == self::SQLITE) {
                foreach ($increment as $name) {
                    $start = (int)$this->columns[$name]['increment'];
                    if (substr((string)$start, -1) == '1') {
                        $start -= 1;
                    }
                    $sql .= 'INSERT INTO "sqlite_sequence" ("name", "seq") ' .
                        'VALUES (' . $this->quoteId($this->table) . ', ' . $start . ');' . PHP_EOL;
                }
                $sql .= PHP_EOL;
            }
        }

        // Create indices
        foreach ($this->indices as $name => $index) {
            foreach ($index['column'] as $i => $column) {
                $index['column'][$i] = $this->quoteId($column);
            }

            if ($index['type'] != 'primary') {
                $sql .= 'CREATE ' . (($index['type'] == 'unique') ? 'UNIQUE ' : null) . 'INDEX ' . $this->quoteId($name) .
                    ' ON ' . $this->quoteId($this->table) . ' (' . implode(', ', $index['column']) . ');' . PHP_EOL;
            }
        }

        // Create constraints
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

    public function __toString()
    {
        return $this->render();
    }

}