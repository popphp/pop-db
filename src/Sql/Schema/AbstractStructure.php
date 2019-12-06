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
namespace Pop\Db\Sql\Schema;

/**
 * Schema abstract design table class for CREATE and ALTER
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractStructure extends AbstractTable
{

    /**
     * Columns to be added or modified
     * @var array
     */
    protected $columns = [];

    /**
     * Indices to be created
     * @var array
     */
    protected $indices = [];

    /**
     * Constraints to be added
     * @var array
     */
    protected $constraints = [];

    /**
     * Current column
     * @var string
     */
    protected $currentColumn = null;

    /**
     * Current constraint
     * @var string
     */
    protected $currentConstraint = null;

    /**
     * Set the current column
     *
     * @param  string $column
     * @return AbstractStructure
     */
    public function column($column)
    {
        $this->currentColumn = $column;
        return $this;
    }

    /**
     * Get the current column
     *
     * @return string
     */
    public function getColumn()
    {
        return $this->currentColumn;
    }

    /**
     * Set the current constraint
     *
     * @param  string $constraint
     * @return AbstractStructure
     */
    public function constraint($constraint)
    {
        $this->currentConstraint = $constraint;
        return $this;
    }

    /**
     * Get the current constraint
     *
     * @return string
     */
    public function getConstraint()
    {
        return $this->currentConstraint;
    }

    /**
     * Add a column
     *
     * @param  string $name
     * @param  string $type
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function addColumn($name, $type, $size = null, $precision = null, array $attributes = [])
    {
        $this->currentColumn  = $name;
        $this->columns[$name] = [
            'type'       => $type,
            'size'       => $size,
            'precision'  => $precision,
            'nullable'   => null,
            'default'    => null,
            'increment'  => false,
            'primary'    => false,
            'unsigned'   => false,
            'attributes' => (!empty($attributes)) ? $attributes : []
        ];

        return $this;
    }

    /**
     * Determine if the table has a column
     *
     * @param  string $name
     * @return boolean
     */
    public function hasColumn($name)
    {
        return (isset($this->columns[$name]));
    }

    /**
     * Add a custom column attribute
     *
     * @param  string $attribute
     * @return AbstractStructure
     */
    public function addColumnAttribute($attribute)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['attributes'][] = $attribute;
        }

        return $this;
    }

    /**
     * Determine if the table has an increment column
     *
     * @return boolean
     */
    public function hasIncrement()
    {
        $result = false;
        foreach ($this->columns as $name => $column) {
            if ($column['increment'] !== false) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Get the increment column(s)
     *
     * @param  boolean $quote
     * @return array
     */
    public function getIncrement($quote = false)
    {
        $result = [];
        foreach ($this->columns as $name => $column) {
            if ($column['increment'] !== false) {
                $result[] = ($quote) ? $this->quoteId($name) : $name;
            }
        }
        return $result;
    }

    /**
     * Determine if the table has a primary key column
     *
     * @return boolean
     */
    public function hasPrimary()
    {
        $result = false;
        foreach ($this->columns as $name => $column) {
            if ($column['primary']) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Get the primary key column(s)
     *
     * @param  boolean $quote
     * @return array
     */
    public function getPrimary($quote = false)
    {
        $result = [];
        foreach ($this->columns as $name => $column) {
            if ($column['primary']) {
                $result[] = ($quote) ? $this->quoteId($name) : $name;
            }
        }
        return $result;
    }

    /**
     * Set the current column as an increment column
     *
     * @param  int $start
     * @return AbstractStructure
     */
    public function increment($start = 1)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['increment'] = (int)$start;
        }

        return $this;
    }

    /**
     * Set the current column's default value
     *
     * @param  mixed $value
     * @return AbstractStructure
     */
    public function defaultIs($value)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['default'] = $value;
            if (null === $value) {
                $this->columns[$this->currentColumn]['nullable'] = true;
            }
        }

        return $this;
    }

    /**
     * Set the current column as nullable
     *
     * @return AbstractStructure
     */
    public function nullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = true;
        }

        return $this;
    }

    /**
     * Set the current column as NOT nullable
     *
     * @return AbstractStructure
     */
    public function notNullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = false;
        }

        return $this;
    }

    /**
     * Set the current column as unsigned
     *
     * @return AbstractStructure
     */
    public function unsigned()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['unsigned'] = true;
        }

        return $this;
    }

    /**
     * Create an index
     *
     * @param  string $column
     * @param  string $name
     * @param  string $type
     * @return AbstractStructure
     */
    public function index($column, $name = null, $type = 'index')
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        foreach ($column as $c) {
            if (isset($this->columns[$c]) && ($type == 'primary')) {
                $this->columns[$c]['primary'] = true;
            }
        }

        if (null === $name) {
            $name = 'index';
            foreach ($column as $c) {
                $name .= '_' . strtolower($c);
            }
        }
        $this->indices[$name] = [
            'column' => $column,
            'type'   => $type
        ];

        return $this;
    }

    /**
     * Create a UNIQUE index
     *
     * @param  string $column
     * @param  string $name
     * @return AbstractStructure
     */
    public function unique($column = null, $name = null)
    {
        if (null === $column) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'unique');
    }

    /**
     * Create a PRIMARY KEY index
     *
     * @param  string $column
     * @param  string $name
     * @return AbstractStructure
     */
    public function primary($column = null, $name = null)
    {
        if (null === $column) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'primary');
    }

    /**
     * Create a FOREIGN KEY constraint
     *
     * @param  string $column
     * @param  string $name
     * @return AbstractStructure
     */
    public function foreignKey($column, $name = null)
    {
        if (null === $name) {
            $name = 'fk_'. strtolower($column);
        }
        $this->currentConstraint  = $name;
        $this->constraints[$name] = [
            'column'     => $column,
            'references' => null,
            'on'         => null,
            'delete'     => 'SET NULL'
        ];
        return $this;
    }

    /**
     * Assign FOREIGN KEY reference table
     *
     * @param  string $foreignTable
     * @return AbstractStructure
     */
    public function references($foreignTable)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['references'] = $foreignTable;
        }

        return $this;
    }

    /**
     * Assign FOREIGN KEY reference table column
     *
     * @param  string $foreignColumn
     * @return AbstractStructure
     */
    public function on($foreignColumn)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['on'] = $foreignColumn;
        }

        return $this;
    }

    /**
     * Assign FOREIGN KEY ON DELETE action
     *
     * @param  string $action
     * @return AbstractStructure
     */
    public function onDelete($action = null)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['delete'] = (strtolower($action) == 'cascade') ?
                'CASCADE' : 'SET NULL';
        }

        return $this;
    }

    /*
     * INTEGER TYPES
     */

    /**
     * Add an INTEGER column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function integer($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'integer', $size, null, $attributes);
    }

    /**
     * Add an INT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function int($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'int', $size, null, $attributes);
    }

    /**
     * Add a BIGINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function bigInt($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'bigint', $size, null, $attributes);
    }

    /**
     * Add a MEDIUMINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function mediumInt($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'mediumint', $size, null, $attributes);
    }

    /**
     * Add a SMALLINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function smallInt($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'smallint', $size, null, $attributes);
    }

    /**
     * Add a TINYINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function tinyInt($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'tinyint', $size, null, $attributes);
    }

    /*
     * NUMERIC TYPES
     */

    /**
     * Add a FLOAT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function float($name, $size = null, $precision = null, array $attributes = [])
    {
        return $this->addColumn($name, 'float', $size, $precision, $attributes);
    }

    /**
     * Add a REAL column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function real($name, $size = null, $precision = null, array $attributes = [])
    {
        return $this->addColumn($name, 'real', $size, $precision, $attributes);
    }

    /**
     * Add a DOUBLE column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function double($name, $size = null, $precision = null, array $attributes = [])
    {
        return $this->addColumn($name, 'double', $size, $precision, $attributes);
    }

    /**
     * Add a DECIMAL column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function decimal($name, $size = null, $precision = null, array $attributes = [])
    {
        return $this->addColumn($name, 'decimal', $size, $precision, $attributes);
    }

    /**
     * Add a NUMERIC column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function numeric($name, $size = null, $precision = null, array $attributes = [])
    {
        return $this->addColumn($name, 'numeric', $size, $precision, $attributes);
    }

    /*
     * DATE & TIME TYPES
     */

    /**
     * Add a DATE column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function date($name, array $attributes = [])
    {
        return $this->addColumn($name, 'date', null, null, $attributes);
    }

    /**
     * Add a TIME column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function time($name, array $attributes = [])
    {
        return $this->addColumn($name, 'time', null, null, $attributes);
    }

    /**
     * Add a DATETIME column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function datetime($name, array $attributes = [])
    {
        return $this->addColumn($name, 'datetime', null, null, $attributes);
    }

    /**
     * Add a TIMESTAMP column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function timestamp($name, array $attributes = [])
    {
        return $this->addColumn($name, 'timestamp', null, null, $attributes);
    }

    /**
     * Add a YEAR column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function year($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'year', $size, null, $attributes);
    }

    /*
     * CHARACTER TYPES
     */

    /**
     * Add a TEXT column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function text($name, array $attributes = [])
    {
        return $this->addColumn($name, 'text', null, null, $attributes);
    }

    /**
     * Add a TINYTEXT column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function tinyText($name, array $attributes = [])
    {
        return $this->addColumn($name, 'tinytext', null, null, $attributes);
    }

    /**
     * Add a MEDIUMTEXT column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function mediumText($name, array $attributes = [])
    {
        return $this->addColumn($name, 'mediumtext', null, null, $attributes);
    }

    /**
     * Add a LONGTEXT column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function longText($name, array $attributes = [])
    {
        return $this->addColumn($name, 'longtext', null, null, $attributes);
    }

    /**
     * Add a BLOB column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function blob($name, array $attributes = [])
    {
        return $this->addColumn($name, 'blob', null, null, $attributes);
    }

    /**
     * Add a MEDIUMBLOB column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function mediumBlob($name, array $attributes = [])
    {
        return $this->addColumn($name, 'mediumblob', null, null, $attributes);
    }

    /**
     * Add a LONGBLOB column
     *
     * @param  string $name
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function longBlob($name, array $attributes = [])
    {
        return $this->addColumn($name, 'longblob', null, null, $attributes);
    }

    /**
     * Add a CHAR column
     *
     * @param  string $name
     * @param  int    $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function char($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'char', $size, null, $attributes);
    }

    /**
     * Add a VARCHAR column
     *
     * @param  string $name
     * @param  int    $size
     * @param  array  $attributes
     * @return AbstractStructure
     */
    public function varchar($name, $size = null, array $attributes = [])
    {
        return $this->addColumn($name, 'varchar', $size, null, $attributes);
    }

    /**
     * Format column schema
     *
     * @param  string $name
     * @param  array $column
     * @return string
     */
    protected function getColumnSchema($name, array $column)
    {
        return Formatter\Column::getColumnSchema($this->getDbType(), $this->quoteId($name), $column, $this->table);
    }

}