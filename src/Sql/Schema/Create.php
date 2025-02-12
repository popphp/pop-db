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

/**
 * Schema CREATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class Create extends AbstractStructure
{

    /**
     * IF NOT EXISTS flag
     * @var bool
     */
    protected bool $ifNotExists = false;

    /**
     * Table engine (MySQL only)
     * @var string
     */
    protected string $engine = 'InnoDB';

    /**
     * Table charset (MySQL only)
     * @var string
     */
    protected string $charset = 'utf8';

    /**
     * Set the IF NOT EXISTS flag
     *
     * @return Create
     */
    public function ifNotExists(): Create
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Set the table engine (MySQL only)
     *
     * @param  string $engine
     * @return Create
     */
    public function setEngine(string $engine): Create
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Get the table engine (MySQL only)
     *
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Set the table charset (MySQL only)
     *
     * @param  string $charset
     * @return Create
     */
    public function setCharset(string $charset): Create
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Get the table charset (MySQL only)
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render(): string
    {
        $schema = '';

        // Create PGSQL sequence
        if (($this->hasIncrement()) && ($this->isPgsql())) {
            $schema .= Formatter\Table::createPgsqlSequences($this->getIncrement(), $this->table, $this->columns);
        }

        /*
         * START CREATE TABLE
         */
        $schema .= 'CREATE TABLE ' . ((($this->ifNotExists) && ($this->dbType != self::SQLSRV)) ? 'IF NOT EXISTS ' : null) .
            $this->quoteId($this->table) . ' (' . PHP_EOL;

        // Iterate over the columns
        $i         = 0;
        $increment = null;
        foreach ($this->columns as $name => $column) {
            if ($column['increment'] !== false) {
                $increment = $column['increment'];
            }
            $schema .= (($i != 0) ? ',' . PHP_EOL : null) . '  ' . $this->getColumnSchema($name, $column);
            $i++;
        }

        // Format primary key schema
        if ($this->hasPrimary()) {
            $schema .= Formatter\Table::formatPrimarySchema($this->dbType, $this->getPrimary(true));
        }

        $schema .= Formatter\Table::formatEndOfTable($this->dbType, $this->engine, $this->charset, $increment);

        /*
         * END CREATE TABLE
         */

        // Assign PGSQL or SQLITE sequences
        if ($this->hasIncrement()) {
            $schema .= Formatter\Table::createSequences(
                $this->dbType, $this->getIncrement(), $this->quoteId($this->table), $this->columns
            );
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
