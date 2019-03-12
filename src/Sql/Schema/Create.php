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

/**
 * Schema CREATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Create extends AbstractStructure
{

    /**
     * IF NOT EXISTS flag
     * @var boolean
     */
    protected $ifNotExists = false;

    /**
     * Table engine (MySQL only)
     * @var string
     */
    protected $engine = 'InnoDB';

    /**
     * Table charset (MySQL only)
     * @var string
     */
    protected $charset = 'utf8';

    /**
     * Set the IF NOT EXISTS flag
     *
     * @return Create
     */
    public function ifNotExists()
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Set the IF NOT EXISTS flag
     *
     * @param  string $engine
     * @return Create
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
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

        // Create PGSQL sequence
        if ($this->hasIncrement()) {
            if ($this->isPgsql()) {
                $increment = $this->getIncrement();
                foreach ($increment as $name) {
                    $sql .= 'CREATE SEQUENCE ' . $this->table . '_' . $name . '_seq START ' .
                        (int)$this->columns[$name]['increment'] . ';';
                }
                $sql .= PHP_EOL . PHP_EOL;
            }
        }

        /*
         * START CREATE TABLE
         */
        $sql .= 'CREATE TABLE ' . ((($this->ifNotExists) && ($this->dbType != self::SQLSRV)) ? 'IF NOT EXISTS ' : null) .
            $this->quoteId($this->table) . ' (' . PHP_EOL;

        $i   = 0;
        $inc = null;
        foreach ($this->columns as $name => $column) {
            if ($column['increment'] !== false) {
                $inc = $column['increment'];
            }
            $sql .= (($i != 0) ? ',' . PHP_EOL : null) . '  ' . $this->quoteId($name) . ' ' .
                $this->getColumnType($name, $column);
            $i++;
        }

        if (($this->hasPrimary()) && ($this->dbType !== self::SQLSRV)) {
            $sql .= ($this->isSqlite()) ?
               ',' . PHP_EOL . '  UNIQUE (' . implode(', ', $this->getPrimary(true)) . ')' :
               ',' . PHP_EOL . '  PRIMARY KEY (' . implode(', ', $this->getPrimary(true)) . ')';
        }

        $sql .= PHP_EOL . ')';

        if ($this->isMysql()) {
            $sql .= ' ENGINE=' . $this->engine;
            $sql .= ' DEFAULT CHARSET=' . $this->charset;
            if (null !== $inc) {
                $sql .= ' AUTO_INCREMENT=' . (int)$inc;
            }
            $sql .= ';' . PHP_EOL . PHP_EOL;
        } else {
            $sql .= ';' . PHP_EOL . PHP_EOL;
        }

        /*
         * END CREATE TABLE
         */

        // Assign PGSQL or SQLITE sequences
        if ($this->hasIncrement()) {
            $increment = $this->getIncrement();
            if ($this->isPgsql()) {
                foreach ($increment as $name) {
                    $sql .= 'ALTER SEQUENCE ' . $this->table . '_' . $name . '_seq OWNED BY ' .
                        $this->quoteId($this->table . '.' . $name) . ';' . PHP_EOL;
                }
                $sql .= PHP_EOL;
            } else if ($this->isSqlite()) {
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

        // Add indices
        foreach ($this->indices as $name => $index) {
            foreach ($index['column'] as $i => $column) {
                $index['column'][$i] = $this->quoteId($column);
            }

            if ($index['type'] != 'primary') {
                $sql .= 'CREATE ' . (($index['type'] == 'unique') ? 'UNIQUE ' : null) . 'INDEX ' . $this->quoteId($name) .
                    ' ON ' . $this->quoteId($this->table) . ' (' . implode(', ', $index['column']) . ');' . PHP_EOL;
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
