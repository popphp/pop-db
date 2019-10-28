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
 * Schema abstract design table class for CREATE and ALTER
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
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
     * @param  string $name
     * @return AbstractStructure
     */
    public function column($name)
    {
        $this->currentColumn = $name;
        return $this;
    }

    /**
     * Set the current constraint
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function constraint($name)
    {
        $this->currentConstraint = $name;
        return $this;
    }

    /**
     * Add a column
     *
     * @param  string $name
     * @param  string $type
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return AbstractStructure
     */
    public function addColumn($name, $type, $size = null, $precision = null)
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
            'attributes' => []
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
     * @throws Exception
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
            $this->constraints[$this->currentConstraint]['delete'] = (strtolower($action) == 'cascade') ? 'CASCADE' : 'SET NULL';
        }

        return $this;
    }

    /*
     * NUMERIC TYPES
     */

    /**
     * Add an INTEGER column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function integer($name, $size = null)
    {
        return $this->addColumn($name, 'integer', $size);
    }

    /**
     * Add an INT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function int($name, $size = null)
    {
        return $this->addColumn($name, 'int', $size);
    }

    /**
     * Add a BIGINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function bigInt($name, $size = null)
    {
        return $this->addColumn($name, 'bigint', $size);
    }

    /**
     * Add a MEDIUMINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function mediumInt($name, $size = null)
    {
        return $this->addColumn($name, 'mediumint', $size);
    }

    /**
     * Add a SMALLINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function smallInt($name, $size = null)
    {
        return $this->addColumn($name, 'smallint', $size);
    }

    /**
     * Add a TINYINT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function tinyInt($name, $size = null)
    {
        return $this->addColumn($name, 'tinyint', $size);
    }

    /**
     * Add a FLOAT column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return AbstractStructure
     */
    public function float($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'float', $size, $precision);
    }

    /**
     * Add a REAL column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function real($name)
    {
        return $this->addColumn($name, 'real');
    }

    /**
     * Add a DOUBLE column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return AbstractStructure
     */
    public function double($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'double', $size, $precision);
    }

    /**
     * Add a DECIMAL column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return AbstractStructure
     */
    public function decimal($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'decimal', $size, $precision);
    }

    /**
     * Add a NUMERIC column
     *
     * @param  string $name
     * @param  mixed  $size
     * @param  mixed  $precision
     * @return AbstractStructure
     */
    public function numeric($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'numeric', $size, $precision);
    }

    /*
     * DATE & TIME TYPES
     */

    /**
     * Add a DATE column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function date($name)
    {
        return $this->addColumn($name, 'date');
    }

    /**
     * Add a TIME column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function time($name)
    {
        return $this->addColumn($name, 'time');
    }

    /**
     * Add a DATETIME column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function datetime($name)
    {
        return $this->addColumn($name, 'datetime');
    }

    /**
     * Add a TIMESTAMP column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function timestamp($name)
    {
        return $this->addColumn($name, 'timestamp');
    }

    /**
     * Add a YEAR column
     *
     * @param  string $name
     * @param  mixed  $size
     * @return AbstractStructure
     */
    public function year($name, $size = null)
    {
        return $this->addColumn($name, 'year', $size);
    }

    /*
     * CHARACTER TYPES
     */

    /**
     * Add a TEXT column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function text($name)
    {
        return $this->addColumn($name, 'text');
    }

    /**
     * Add a TINYTEXT column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function tinyText($name)
    {
        return $this->addColumn($name, 'tinytext');
    }

    /**
     * Add a MEDIUMTEXT column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function mediumText($name)
    {
        return $this->addColumn($name, 'mediumtext');
    }

    /**
     * Add a LONGTEXT column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function longText($name)
    {
        return $this->addColumn($name, 'longtext');
    }

    /**
     * Add a BLOB column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function blob($name)
    {
        return $this->addColumn($name, 'blob');
    }

    /**
     * Add a MEDIUMBLOB column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function mediumBlob($name)
    {
        return $this->addColumn($name, 'mediumblob');
    }

    /**
     * Add a LONGBLOB column
     *
     * @param  string $name
     * @return AbstractStructure
     */
    public function longBlob($name)
    {
        return $this->addColumn($name, 'longblob');
    }

    /**
     * Add a CHAR column
     *
     * @param  string $name
     * @param  int    $size
     * @return AbstractStructure
     */
    public function char($name, $size = null)
    {
        return $this->addColumn($name, 'char', $size);
    }

    /**
     * Add a VARCHAR column
     *
     * @param  string $name
     * @param  int    $size
     * @return AbstractStructure
     */
    public function varchar($name, $size = null)
    {
        return $this->addColumn($name, 'varchar', $size);
    }

    /**
     * Format column data type and parameters
     *
     * @param  string $name
     * @param  array $column
     * @return string
     */
    protected function getColumnType($name, array $column)
    {
        $columnString = $this->getValidColumnType($column['type']);

        if (!empty($column['size']) && !($this->isSqlite())) {
            $columnString .= '(' . $column['size'];
            $columnString .= (!empty($column['precision'])) ? ', ' . $column['precision'] . ')' : ')';
        }

        if (($this->isMysql()) && ($column['unsigned'] !== false)) {
            $columnString .= ' UNSIGNED';
        }

        if (count($column['attributes']) > 0) {
            $columnString .= ' ' . implode(' ', $column['attributes']);
        }

        if (($column['nullable'] === false) || (strtoupper($column['default']) == 'NOT NULL')) {
            $columnString .= ' NOT NULL';
        }

        if ((null === $column['default']) && ($column['nullable'] === true)) {
            $columnString .= ' DEFAULT NULL';
        } else if (!empty($column['default'])) {
            if (strtoupper($column['default']) == 'NULL') {
                $columnString .= ' DEFAULT NULL';
            }
        }

        if ($column['increment'] !== false) {
            switch ($this->dbType) {
                case (self::MYSQL):
                    $columnString .= ' AUTO_INCREMENT';
                    break;
                case (self::SQLITE):
                    $columnString .= (($column['primary'] !== false) ? ' PRIMARY KEY' : null) . ' AUTOINCREMENT';
                    break;
                case (self::PGSQL):
                    $columnString .= ' nextval(\'' . $this->table . '_' . $name . '_seq\')';
                    break;
                case (self::SQLSRV):
                    $columnString .= (($column['primary'] !== false) ? ' PRIMARY KEY' : null) .
                        ' IDENTITY(' . (int)$column['increment'] . ', 1)';
                    break;
            }
        }

        return $columnString;
    }

    /**
     * Get valid column type
     *
     * @param  string $type
     * @return string
     */
    protected function getValidColumnType($type)
    {
        $type = strtoupper($type);

        if ($this->isMysql()) {
            switch ($type) {
                case 'INTEGER':
                    $type = 'INT';
                    break;
                case 'SERIAL':
                    $type = 'INT';
                    break;
                case 'BIGSERIAL':
                    $type = 'BIGINT';
                    break;
                case 'SMALLSERIAL':
                    $type = 'SMALLINT';
                    break;
            }
        } else if ($this->isPgsql()) {
            switch ($type) {
                case 'TINYINT':
                    $type = 'INT';
                    break;
                case 'MEDIUMINT':
                    $type = 'INT';
                    break;
                case 'DATETIME':
                    $type = 'TIMESTAMP';
                    break;
                case 'VARBINARY':
                    $type = 'BYTEA';
                    break;
                case 'BLOB':
                case 'TINYBLOB':
                case 'MEDIUMBLOB':
                case 'LONGBLOB':
                case 'TINYTEXT':
                case 'MEDIUMTEXT':
                case 'LONGTEXT':
                    $type = 'TEXT';
                    break;
            }
        } else if ($this->isSqlsrv()) {
            switch ($type) {
                case 'INTEGER':
                    $type = 'INT';
                    break;
                case 'MEDIUMINT':
                    $type = 'INT';
                    break;
                case 'SERIAL':
                    $type = 'INT';
                    break;
                case 'BIGSERIAL':
                    $type = 'BIGINT';
                    break;
                case 'SMALLSERIAL':
                    $type = 'SMALLINT';
                    break;
                case 'TIMESTAMP':
                    $type = 'DATETIME2';
                    break;
                case 'BLOB':
                case 'TINYBLOB':
                case 'MEDIUMBLOB':
                case 'LONGBLOB':
                case 'TINYTEXT':
                case 'MEDIUMTEXT':
                case 'LONGTEXT':
                    $type = 'TEXT';
                    break;
            }
        } else if ($this->isSqlite()) {
            switch ($type) {
                case 'INT':
                    $type = 'INTEGER';
                    break;
                case 'SERIAL':
                    $type = 'INT';
                    break;
                case 'BIGSERIAL':
                    $type = 'BIGINT';
                    break;
                case 'SMALLSERIAL':
                    $type = 'SMALLINT';
                    break;
                case 'TIMESTAMP':
                    $type = 'DATETIME';
                    break;
            }
        }

        return $type;
    }

}