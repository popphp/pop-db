<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record;

use Pop\Db\Gateway;
use Pop\Db\Sql\Parser;
use ReturnTypeWillChange;

/**
 * Abstract record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
abstract class AbstractRecord implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = null;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Row gateway
     * @var Gateway\Row
     */
    protected $rowGateway = null;

    /**
     * Table gateway
     * @var Gateway\Table
     */
    protected $tableGateway = null;

    /**
     * Is new record flag
     * @var bool
     */
    protected $isNew = false;

    /**
     * With relationships
     * @var array
     */
    protected $with = [];

    /**
     * With relationship options
     * @var array
     */
    protected $withOptions = [];

    /**
     * With relationship children
     * @var string
     */
    protected $withChildren = null;

    /**
     * Relationships
     * @var array
     */
    protected $relationships = [];

    /**
     * Set the table
     *
     * @param  string $table
     * @return AbstractRecord
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table from a class name
     *
     * @param  string $class
     * @return mixed
     */
    public function setTableFromClassName($class = null)
    {
        if ($class === null) {
            $class = get_class($this);
        }

        if (strpos($class, '_') !== false) {
            $cls = substr($class, (strrpos($class, '_') + 1));
        } else if (strpos($class, '\\') !== false) {
            $cls = substr($class, (strrpos($class, '\\') + 1));
        } else {
            $cls = $class;
        }

        return $this->setTable(Parser\Table::parse($cls));
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return AbstractRecord
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return AbstractRecord
     */
    public function setPrimaryKeys(array $keys)
    {
        $this->primaryKeys = $keys;
        return $this;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the full table name (prefix + table)
     *
     * @return string
     */
    public function getFullTable()
    {
        return $this->prefix . $this->table;
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the primary keys
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Get the primary values
     *
     * @return array
     */
    public function getPrimaryValues()
    {
        return ($this->rowGateway !== null) ?
            array_intersect_key($this->rowGateway->getColumns(), array_flip($this->primaryKeys)) : [];
    }

    /**
     * Get the row gateway
     *
     * @return Gateway\Row
     */
    public function getRowGateway()
    {
        return $this->rowGateway;
    }

    /**
     * Get the table gateway
     *
     * @return Gateway\Table
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        $columns = $this->rowGateway->getColumns();

        if ($this->hasRelationships()) {
            $relationships = $this->getRelationships();
            foreach ($relationships as $name => $relationship) {
                $columns[$name] = (is_object($relationship) && method_exists($relationship, 'toArray')) ?
                    $relationship->toArray() : $relationship;
            }
        }

        return $columns;
    }

    /**
     * Method to get count of fields in row gateway
     *
     * @return int
     */
    public function count(): int
    {
        return $this->rowGateway->count();
    }

    /**
     * Method to iterate over the columns
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->rowGateway->getIterator();
    }

    /**
     * Get the rows
     *
     * @return Collection
     */
    public function getRows()
    {
        return new Collection($this->tableGateway->getRows());
    }

    /**
     * Get the rows (alias method)
     *
     * @return Collection
     */
    public function rows()
    {
        return $this->getRows();
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function countRows()
    {
        return $this->tableGateway->getNumberOfRows();
    }

    /**
     * Determine if the result has rows
     *
     * @return bool
     */
    public function hasRows()
    {
        return ($this->tableGateway->getNumberOfRows() > 0);
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return AbstractRecord
     */
    public function setColumns($columns = null)
    {
        if ($columns !== null) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->rowGateway->setColumns((array)$columns);
            } else if ($columns instanceof AbstractRecord) {
                $this->rowGateway->setColumns($columns->toArray());
            } else if (($columns instanceof \ArrayAccess) && method_exists($columns, 'toArray')) {
                $this->rowGateway->setColumns($columns->toArray());
            } else {
                throw new Exception('The parameter passed must be an arrayable object.');
            }
        }

        return $this;
    }

    /**
     * Set all the table rows at once
     *
     * @param  array   $rows
     * @param  bool $asArray
     * @return AbstractRecord
     */
    public function setRows(array $rows = null, $asArray = false)
    {
        $this->rowGateway->setColumns();
        $this->tableGateway->setRows();

        if ($rows !== null) {
            $this->rowGateway->setColumns(((isset($rows[0])) ? (array)$rows[0] : []));
            foreach ($rows as $i => $row) {
                $rows[$i] = $this->processRow($row, $asArray);
            }
            $this->tableGateway->setRows($rows);
        }

        return $this;
    }

    /**
     * Process table rows
     *
     * @param  array   $rows
     * @param  bool $asArray
     * @return array
     */
    public function processRows(array $rows, $asArray = false)
    {
        foreach ($rows as $i => $row) {
            $rows[$i] = $this->processRow($row, $asArray);
        }
        return $rows;
    }

    /**
     * Process a table row
     *
     * @param  array   $row
     * @param  bool $asArray
     * @return mixed
     */
    public function processRow(array $row, $asArray = false)
    {
        if ($asArray) {
            return $row;
        } else {
            $record = new static();
            $record->setColumns((array)$row);
            return $record;
        }
    }

    /**
     * Set with relationships
     *
     * @param  string $name
     * @param  array  $options
     * @return AbstractRecord
     */
    public function addWith($name, array $options = null)
    {
        $children = null;
        if (strpos($name, '.') !== false) {
            $names    = explode('.', $name);
            $name     = array_shift($names);
            $children = implode('.', $names);
        }
        $this->with[]        = $name;
        $this->withOptions[] = $options;
        $this->withChildren  = $children;

        return $this;
    }

    /**
     * Determine if there is specific with relationship
     *
     * @param  string  $name
     * @return bool
     */
    public function hasWith($name)
    {
        return (isset($this->with[$name]));
    }

    /**
     * Determine if there are with relationships
     *
     * @return bool
     */
    public function hasWiths()
    {
        return (count($this->with) > 0);
    }

    /**
     * Get with relationships
     *
     * @return array
     */
    public function getWiths()
    {
        return $this->with;
    }

    /**
     * Get with relationships
     *
     * @param  bool $eager
     * @return AbstractRecord
     */
    public function getWithRelationships($eager = true)
    {
        foreach ($this->with as $i => $name) {
            $options = (isset($this->withOptions[$i])) ? $this->withOptions[$i] : null;

            if (method_exists($this, $name)) {
                $this->relationships[$name] = $this->{$name}($options, $eager);
            }
        }

        return $this;
    }

    /**
     * Process with relationships
     *
     * @param  array $rows
     * @return AbstractRecord
     */
    public function processWithRelationships(array $rows)
    {
        foreach ($this->relationships as $name => $relationship) {
            $withIds = [];
            if ($relationship instanceof \Pop\Db\Record\Relationships\HasOneOf) {
                $primaryKey = $relationship->getForeignKey();
                foreach ($rows as $i => $row) {
                    if (isset($row[$primaryKey]) && !in_array($row[$primaryKey], $withIds)) {
                        $withIds[] = $row[$primaryKey];
                    }
                }
                $results = $relationship->getEagerRelationships($withIds);
            } else {
                $primaryKey = $this->getPrimaryKeys();
                if (count($primaryKey) == 1) {
                    $primaryKey = reset($primaryKey);
                }
                foreach ($rows as $i => $row) {
                    $primaryValues = $rows[$i]->getPrimaryValues();
                    if (count($primaryValues) == 1) {
                        $withId = reset($primaryValues);
                        if (!in_array($withId, $withIds)) {
                            $withIds[] = $withId;
                        }
                    }
                }
                $results = $relationship->getEagerRelationships($withIds);
            }
            foreach ($rows as $i => $row) {
                if (isset($results[$row[$primaryKey]])) {
                    $row->setRelationship($name, $results[$row[$primaryKey]]);
                } else {
                    $row->setRelationship($name, []);
                }
            }
        }

        return $this;
    }

    /**
     * Set relationship
     *
     * @param  string $name
     * @param  mixed  $relationship
     * @return AbstractRecord
     */
    public function setRelationship($name, $relationship)
    {
        $this->relationships[$name] = $relationship;
        return $this;
    }

    /**
     * Get relationship
     *
     * @param  string $name
     * @return mixed
     */
    public function getRelationship($name)
    {
        return (isset($this->relationships[$name])) ? $this->relationships[$name] : null;
    }

    /**
     * Has relationship
     *
     * @param  string $name
     * @return bool
     */
    public function hasRelationship($name)
    {
        return (isset($this->relationships[$name]));
    }

    /**
     * Get relationships
     *
     * @return array
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Get relationships
     *
     * @return bool
     */
    public function hasRelationships()
    {
        return (count($this->relationships) > 0);
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->rowGateway[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $result = null;

        if (isset($this->relationships[$name])) {
            $result = $this->relationships[$name];
        } else if (isset($this->rowGateway[$name])) {
            $result = $this->rowGateway[$name];
        } else if (method_exists($this, $name)) {
            $result = $this->{$name}();
        }

        return $result;
    }

    /**
     * Magic method to return the isset value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        if (isset($this->relationships[$name])) {
            return true;
        } else if (isset($this->rowGateway[$name])) {
            return true;
        } else if (method_exists($this, $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Magic method to unset $this->rowGateway[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->rowGateway[$name])) {
            unset($this->rowGateway[$name]);
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}