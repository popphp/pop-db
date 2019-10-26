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
namespace Pop\Db\Gateway;

use Pop\Db\Db;

/**
 * Row gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Row extends AbstractGateway implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = [];

    /**
     * Primary values
     * @var array
     */
    protected $primaryValues = [];

    /**
     * Row column values
     * @var array
     */
    protected $columns = [];

    /**
     * Row fields that have been changed
     * @var array
     */
    protected $dirty = [
        'old' => [],
        'new' => []
    ];

    /**
     * Constructor
     *
     * Instantiate the row gateway object.
     *
     * @param  string $table
     * @param  mixed  $primaryKeys
     */
    public function __construct($table, $primaryKeys = null)
    {
        if (null !== $primaryKeys) {
            $this->setPrimaryKeys($primaryKeys);
        }
        parent::__construct($table);
    }

    /**
     * Set the primary keys
     *
     * @param  mixed $keys
     * @return Row
     */
    public function setPrimaryKeys($keys)
    {
        $this->primaryKeys = (is_array($keys)) ? $keys : [$keys];
        return $this;
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
     * Set the primary values
     *
     * @param  mixed $values
     * @return Row
     */
    public function setPrimaryValues($values)
    {
        $this->primaryValues = (is_array($values)) ? $values : [$values];
        return $this;
    }

    /**
     * Get the primary values
     *
     * @return array
     */
    public function getPrimaryValues()
    {
        return $this->primaryValues;
    }

    /**
     * Determine if number of primary keys and primary values match
     *
     * @throws Exception
     * @return void
     */
    public function doesPrimaryCountMatch()
    {
        if (count($this->primaryKeys) != count($this->primaryValues)) {
            throw new Exception('Error: The number of primary keys and primary values do not match.');
        }
    }

    /**
     * Set the columns
     *
     * @param  array $columns
     * @return Row
     */
    public function setColumns(array $columns = [])
    {
        $this->columns = $columns;
        if (count($this->primaryValues) == 0) {
            foreach ($this->primaryKeys as $key) {
                if (isset($this->columns[$key])) {
                    $this->primaryValues[] = $this->columns[$key];
                }
            }
        }

        return $this;
    }

    /**
     * Get the columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Check if row data is dirty
     *
     * @return boolean
     */
    public function isDirty()
    {
        return ($this->dirty['old'] !== $this->dirty['new']);
    }

    /**
     * Get dirty columns
     *
     * @return array
     */
    public function getDirty()
    {
        return $this->dirty;
    }

    /**
     * Reset dirty columns
     *
     * @return Row
     */
    public function resetDirty()
    {
        $this->dirty['old'] = [];
        $this->dirty['new'] = [];
        return $this;
    }

    /**
     * Find row by primary key values
     *
     * @param  mixed $values
     * @throws Exception
     * @return array
     */
    public function find($values)
    {
        if (count($this->primaryKeys) == 0) {
            throw new Exception('Error: The primary key(s) have not been set.');
        }

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        $this->setPrimaryValues($values);
        $this->doesPrimaryCountMatch();

        $sql->select([$this->table . '.*'])->from($this->table);

        $params = [];

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }

            if (null === $this->primaryValues[$i]) {
                $sql->select()->where->isNull($primaryKey);
            } else {
                $sql->select()->where->equalTo($primaryKey, $placeholder);
                $params[$primaryKey] = $this->primaryValues[$i];
            }
        }

        $sql->select()->limit(1);

        $db->prepare((string)$sql)
             ->bindParams($params)
             ->execute();

        $row = $db->fetch();

        if (($row !== false) && is_array($row)) {
            $this->columns = $row;
        }

        return $this->columns;
    }

    /**
     * Save a new row in the table
     *
     * @return Row
     */
    public function save()
    {
        $db     = Db::getDb($this->table);
        $sql    = $db->createSql();
        $values = [];
        $params = [];

        $i = 1;
        foreach ($this->columns as $column => $value) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }
            $values[$column] = $placeholder;
            $params[$column] = $value;
            $i++;
        }

        $sql->insert($this->table)->values($values);

        $db->prepare((string)$sql)
           ->bindParams($params)
           ->execute();

        if ((count($this->primaryKeys) == 1) && !isset($this->columns[$this->primaryKeys[0]])) {
            $this->columns[$this->primaryKeys[0]] = $db->getLastId();
            $this->primaryValues[] = $this->columns[$this->primaryKeys[0]];
        }

        $this->dirty['old'] = [];
        $this->dirty['new'] = $this->columns;

        return $this;
    }

    /**
     * Update an existing row in the table
     *
     * @throws Exception
     * @return Row
     */
    public function update()
    {
        $db     = Db::getDb($this->table);
        $sql    = $db->createSql();
        $values = [];
        $params = [];

        $oldKeys     = array_keys($this->dirty['old']);
        $newKeys     = array_keys($this->dirty['new']);
        $columnNames = ($oldKeys == $newKeys) ? $newKeys : [];

        $i = 1;
        foreach ($this->columns as $column => $value) {
            if (!in_array($column, $this->primaryKeys) && ((empty($columnNames)) || (!empty($columnNames) && in_array($column, $columnNames)))) {
                $placeholder = $sql->getPlaceholder();

                if ($placeholder == ':') {
                    $placeholder .= $column;
                } else if ($placeholder == '$') {
                    $placeholder .= $i;
                }
                $values[$column] = $placeholder;
                $params[$column] = $value;
                $i++;
            }
        }

        $sql->update($this->table)->values($values);

        foreach ($this->primaryKeys as $key => $primaryKey) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }

            if (array_key_exists($key, $this->primaryValues)) {
                if (null === $this->primaryValues[$key]) {
                    $sql->update()->where->isNull($primaryKey);
                } else {
                    $sql->update()->where->equalTo($primaryKey, $placeholder);
                }
            }

            if (array_key_exists($key, $this->primaryValues)) {
                if (null !== $this->primaryValues[$key]) {
                    $params[$this->primaryKeys[$key]] = $this->primaryValues[$key];
                }
            } else if (array_key_exists($this->primaryKeys[$key], $this->columns)) {
                if (null !== $this->primaryValues[$key]) {
                    if (substr($placeholder, 0, 1) == ':') {
                        $params[$this->primaryKeys[$key]] = $this->columns[$this->primaryKeys[$key]];
                    } else {
                        $params[$key] = $this->columns[$this->primaryKeys[$key]];
                    }
                }
            } else {
                throw new Exception('Error: The value of \'' . $key . '\' is not set');
            }
            $i++;
        }

        $db->prepare((string)$sql)
           ->bindParams($params)
           ->execute();

        return $this;
    }

    /**
     * Delete row from the table using the primary key(s)
     *
     * @throws Exception
     * @return Row
     */
    public function delete()
    {
        if (count($this->primaryKeys) == 0) {
            throw new Exception('Error: The primary key(s) have not been set.');
        }

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        $this->doesPrimaryCountMatch();

        $sql->delete($this->table);

        $params = [];
        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            if (null === $this->primaryValues[$i]) {
                $sql->delete()->where->isNull($primaryKey);
            } else {
                $sql->delete()->where->equalTo($primaryKey, $placeholder);
                $params[$primaryKey] = $this->primaryValues[$i];
            }
        }

        $db->prepare((string)$sql)
           ->bindParams($params)
           ->execute();

        $this->dirty['old'] = $this->columns;
        $this->dirty['new'] = [];

        $this->columns       = [];
        $this->primaryValues = [];

        return $this;
    }

    /**
     * Method to get the count of items in the row
     *
     * @return int
     */
    public function count()
    {
        return count($this->columns);
    }

    /**
     * Method to iterate over the columns
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->columns);
    }

    /**
     * Magic method to set the property to the value of $this->columns[$name].
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (!isset($this->dirty['old'][$name])) {
            if (isset($this->columns[$name]) && ($value != $this->columns[$name])) {
                $this->dirty['old'][$name] = $this->columns[$name];
                $this->dirty['new'][$name] = $value;
            } else if (!isset($this->columns[$name]) && !empty($value)) {
                $this->dirty['old'][$name] = null;
                $this->dirty['new'][$name] = $value;
            }
        }
        $this->columns[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->columns[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->columns[$name])) ? $this->columns[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->columns[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * Magic method to unset $this->columns[$name].
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->columns[$name])) {
            if (!isset($this->dirty['old'][$name])) {
                $this->dirty['old'][$name] = $this->columns[$name];
                $this->dirty['new'][$name] = null;
            }
            unset($this->columns[$name]);
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @throws Exception
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @throws Exception
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}