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
 * Schema abstract design table class for CREATE and ALTER
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
abstract class AbstractStructure extends AbstractTable
{

    /**
     * Columns to be added or modified
     * @var array
     */
    protected array $columns = [];

    /**
     * Indices to be created
     * @var array
     */
    protected array $indices = [];

    /**
     * Constraints to be added
     * @var array
     */
    protected array $constraints = [];

    /**
     * Current column
     * @var ?string
     */
    protected ?string $currentColumn = null;

    /**
     * Current constraint
     * @var ?string
     */
    protected ?string $currentConstraint = null;

    /**
     * Set the current column
     *
     * @param  string $column
     * @return AbstractStructure
     */
    public function column(string $column): AbstractStructure
    {
        $this->currentColumn = $column;
        return $this;
    }

    /**
     * Get the current column
     *
     * @return ?string
     */
    public function getColumn(): ?string
    {
        return $this->currentColumn;
    }

    /**
     * Set the current constraint
     *
     * @param  string $constraint
     * @return AbstractStructure
     */
    public function constraint(string $constraint): AbstractStructure
    {
        $this->currentConstraint = $constraint;
        return $this;
    }

    /**
     * Get the current constraint
     *
     * @return ?string
     */
    public function getConstraint(): ?string
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
    public function addColumn(string $name, string $type, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
            'attributes' => (!empty($attributes)) ? $attributes : [],
            'after'      => null
        ];

        return $this;
    }

    /**
     * Determine if the table has a column
     *
     * @param  string $name
     * @return bool
     */
    public function hasColumn(string $name): bool
    {
        return (isset($this->columns[$name]));
    }

    /**
     * Add a custom column attribute
     *
     * @param  string $attribute
     * @return AbstractStructure
     */
    public function addColumnAttribute(string $attribute): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['attributes'][] = $attribute;
        }

        return $this;
    }

    /**
     * Determine if the table has an increment column
     *
     * @return bool
     */
    public function hasIncrement(): bool
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
     * @param  bool $quote
     * @return array
     */
    public function getIncrement(bool $quote = false): array
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
     * @return bool
     */
    public function hasPrimary(): bool
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
     * @param  bool $quote
     * @return array
     */
    public function getPrimary(bool$quote = false): array
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
    public function increment(int $start = 1): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['increment'] = $start;
        }

        return $this;
    }

    /**
     * Set the current column's default value
     *
     * @param  mixed $value
     * @return AbstractStructure
     */
    public function defaultIs(mixed $value): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['default'] = $value;
            if ($value === null) {
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
    public function nullable(): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['nullable'] = true;
        }

        return $this;
    }

    /**
     * Set the current column as NOT nullable
     *
     * @return AbstractStructure
     */
    public function notNullable(): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['nullable'] = false;
        }

        return $this;
    }

    /**
     * Set the current column as unsigned
     *
     * @return AbstractStructure
     */
    public function unsigned(): AbstractStructure
    {
        if ($this->currentColumn !== null) {
            $this->columns[$this->currentColumn]['unsigned'] = true;
        }

        return $this;
    }

    /**
     * Create an index
     *
     * @param  string|array $column
     * @param  ?string $name
     * @param  string $type
     * @return AbstractStructure
     */
    public function index(string|array $column, ?string $name = null, string $type = 'index'): AbstractStructure
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        foreach ($column as $c) {
            if (isset($this->columns[$c]) && ($type == 'primary')) {
                $this->columns[$c]['primary'] = true;
            }
        }

        if ($name === null) {
            $name = 'index';
            foreach ($column as $c) {
                $name .= '_' . strtolower((string)$c);
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
     * @param  string|array|null $column
     * @param  ?string $name
     * @return AbstractStructure
     */
    public function unique(string|array|null $column = null, ?string $name = null): AbstractStructure
    {
        if ($column === null) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'unique');
    }

    /**
     * Create a PRIMARY KEY index
     *
     * @param  string|array|null $column
     * @param  ?string $name
     * @return AbstractStructure
     */
    public function primary(string|array|null $column = null, ?string $name = null): AbstractStructure
    {
        if ($column === null) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'primary');
    }

    /**
     * Create a FOREIGN KEY constraint
     *
     * @param  string  $column
     * @param  ?string $name
     * @return AbstractStructure
     */
    public function foreignKey(string $column, ?string $name = null): AbstractStructure
    {
        if ($name === null) {
            $name = 'fk_'. strtolower((string)$column);
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
    public function references(string $foreignTable): AbstractStructure
    {
        if ($this->currentConstraint !== null) {
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
    public function on(string $foreignColumn): AbstractStructure
    {
        if ($this->currentConstraint !== null) {
            $this->constraints[$this->currentConstraint]['on'] = $foreignColumn;
        }

        return $this;
    }

    /**
     * Assign FOREIGN KEY ON DELETE action
     *
     * @param  ?string $action
     * @return AbstractStructure
     */
    public function onDelete(?string $action = null): AbstractStructure
    {
        if ($this->currentConstraint !== null) {
            $this->constraints[$this->currentConstraint]['delete'] = (strtolower((string)$action) == 'cascade') ?
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
    public function integer(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function int(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function bigInt(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function mediumInt(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function smallInt(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function tinyInt(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function float(string $name, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
    public function real(string $name, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
    public function double(string $name, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
    public function decimal(string $name, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
    public function numeric(string $name, mixed $size = null, mixed $precision = null, array $attributes = []): AbstractStructure
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
    public function date(string $name, array $attributes = []): AbstractStructure
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
    public function time(string $name, array $attributes = []): AbstractStructure
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
    public function datetime(string $name, array $attributes = []): AbstractStructure
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
    public function timestamp(string $name, array $attributes = []): AbstractStructure
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
    public function year(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function text(string $name, array $attributes = []): AbstractStructure
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
    public function tinyText(string $name, array $attributes = []): AbstractStructure
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
    public function mediumText(string $name, array $attributes = []): AbstractStructure
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
    public function longText(string $name, array $attributes = []): AbstractStructure
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
    public function blob(string $name, array $attributes = []): AbstractStructure
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
    public function mediumBlob(string $name, array $attributes = []): AbstractStructure
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
    public function longBlob(string $name, array $attributes = []): AbstractStructure
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
    public function char(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    public function varchar(string $name, mixed $size = null, array $attributes = []): AbstractStructure
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
    protected function getColumnSchema($name, array $column): string
    {
        return Formatter\Column::getColumnSchema($this->getDbType(), $this->quoteId($name), $column, $this->table);
    }

}
