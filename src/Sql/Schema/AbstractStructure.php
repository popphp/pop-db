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
 * Schema abstract design table class for CREATE and ALTER
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractStructure extends AbstractTable
{

    protected $columns           = [];
    protected $indices           = [];
    protected $constraints       = [];
    protected $currentColumn     = null;
    protected $currentConstraint = null;

    public function column($name)
    {
        $this->currentColumn = $name;
        return $this;
    }

    public function constraint($name)
    {
        $this->currentConstraint = $name;
        return $this;
    }

    public function addColumn($name, $type, $size = null, $precision = null)
    {
        $this->currentColumn  = $name;
        $this->columns[$name] = [
            'type'      => $type,
            'size'      => $size,
            'precision' => $precision,
            'nullable'  => null,
            'default'   => null,
            'increment' => false,
            'primary'   => false,
            'unsigned'  => false
        ];

        return $this;
    }

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

    public function increment($start = 1)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['increment'] = (int)$start;
        }

        return $this;
    }

    public function default($value)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['default'] = $value;
        }

        return $this;
    }

    public function nullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = true;
        }

        return $this;
    }

    public function notNullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = false;
        }

        return $this;
    }

    public function unsigned()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['unsigned'] = true;
        }

        return $this;
    }

    public function index($column, $name = null, $type = 'index')
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        foreach ($column as $c) {
            if (!isset($this->columns[$c])) {
                throw new Exception('Error: That column has not been added to the table schema.');
            }
            if ($type == 'primary') {
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

    public function unique($column = null, $name = null)
    {
        if (null === $column) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'unique');
    }

    public function primary($column = null, $name = null)
    {
        if (null === $column) {
            $column = $this->currentColumn;
        }
        return $this->index($column, $name, 'primary');
    }

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

    public function references($foreignTable)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['references'] = $foreignTable;
        }

        return $this;
    }

    public function on($foreignColumn)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['on'] = $foreignColumn;
        }

        return $this;
    }

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

    public function integer($name, $size = null)
    {
        return $this->addColumn($name, 'integer', $size);
    }

    public function serial($name, $size = null)
    {
        return $this->addColumn($name, 'serial', $size);
    }

    public function bigSerial($name, $size = null)
    {
        return $this->addColumn($name, 'bigserial', $size);
    }

    public function smallSerial($name, $size = null)
    {
        return $this->addColumn($name, 'smallserial', $size);
    }

    public function int($name, $size = null)
    {
        return $this->addColumn($name, 'int', $size);
    }

    public function bigInt($name, $size = null)
    {
        return $this->addColumn($name, 'bigint', $size);
    }

    public function mediumInt($name, $size = null)
    {
        return $this->addColumn($name, 'mediumint', $size);
    }

    public function smallInt($name, $size = null)
    {
        return $this->addColumn($name, 'smallint', $size);
    }

    public function tinyInt($name, $size = null)
    {
        return $this->addColumn($name, 'tinyint', $size);
    }

    public function float($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'float', $size, $precision);
    }

    public function real($name)
    {
        return $this->addColumn($name, 'real');
    }

    public function double($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'double', $size, $precision);
    }

    public function decimal($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'decimal', $size, $precision);
    }

    public function numeric($name, $size = null, $precision = null)
    {
        return $this->addColumn($name, 'numeric', $size, $precision);
    }

    /*
     * DATE & TIME TYPES
     */

    public function date($name)
    {
        return $this->addColumn($name, 'date');
    }

    public function time($name)
    {
        return $this->addColumn($name, 'time');
    }

    public function datetime($name)
    {
        return $this->addColumn($name, 'datetime');
    }

    public function timestamp($name)
    {
        return $this->addColumn($name, 'timestamp');
    }

    public function year($name, $size = null)
    {
        return $this->addColumn($name, 'year', $size);
    }

    /*
     * CHARACTER TYPES
     */

    public function text($name)
    {
        return $this->addColumn($name, 'text');
    }

    public function tinyText($name)
    {
        return $this->addColumn($name, 'tinytext');
    }

    public function mediumText($name)
    {
        return $this->addColumn($name, 'mediumtext');
    }

    public function longText($name)
    {
        return $this->addColumn($name, 'longtext');
    }

    public function blob($name)
    {
        return $this->addColumn($name, 'blob');
    }

    public function mediumBlob($name)
    {
        return $this->addColumn($name, 'mediumblob');
    }

    public function longBlob($name)
    {
        return $this->addColumn($name, 'longblob');
    }

    public function char($name, $size = null)
    {
        return $this->addColumn($name, 'char', $size);
    }

    public function varchar($name, $size = null)
    {
        return $this->addColumn($name, 'varchar', $size);
    }

    protected function getColumnType(array $column)
    {
        $columnString = $this->getValidColumnType($column['type']);

        if (!empty($column['size'])) {
            $columnString .= '(' . $column['size'];
            $columnString .= (!empty($column['precision'])) ? ', ' . $column['precision'] . ')' : ')';
        }

        if ($column['nullable'] === false) {
            $columnString .= ' NOT NULL';
        }

        if ((null === $column['default']) && ($column['nullable'] === true)) {
            $columnString .= ' DEFAULT NULL';
        } else if (!empty($column['default'])) {
            $columnString .= ' DEFAULT \'' . $column['default'] . '\'';
        }

        if ($column['increment'] !== false) {
            if ($this->dbType == self::MYSQL) {
                $columnString .= ' AUTO_INCREMENT';
            } else if ($this->dbType == self::SQLITE) {
                $columnString .= ' AUTOINCREMENT';
            }
        }

        return $columnString;
    }

    protected function getValidColumnType($type)
    {
        return $type;
    }

}