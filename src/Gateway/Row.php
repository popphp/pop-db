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
namespace Pop\Db\Gateway;

use Pop\Db\Db;
use ArrayIterator;

/**
 * Row gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Row extends AbstractGateway implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Primary keys
     * @var array
     */
    protected array $primaryKeys = [];

    /**
     * Primary values
     * @var array
     */
    protected array $primaryValues = [];

    /**
     * Row column values
     * @var array
     */
    protected array $columns = [];

    /**
     * Row fields that have been changed
     * @var array
     */
    protected array $dirty = [
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
    public function __construct(string $table, mixed $primaryKeys = null)
    {
        if ($primaryKeys !== null) {
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
    public function setPrimaryKeys(mixed $keys): Row
    {
        $this->primaryKeys = (is_array($keys)) ? $keys : [$keys];
        return $this;
    }

    /**
     * Get the primary keys
     *
     * @return array
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }

    /**
     * Set the primary values
     *
     * @param  mixed $values
     * @return Row
     */
    public function setPrimaryValues(mixed $values): Row
    {
        $this->primaryValues = (is_array($values)) ? $values : [$values];
        return $this;
    }

    /**
     * Get the primary values
     *
     * @return array
     */
    public function getPrimaryValues(): array
    {
        return $this->primaryValues;
    }

    /**
     * Determine if number of primary keys and primary values match
     *
     * @throws Exception
     * @return bool
     */
    public function doesPrimaryCountMatch(): bool
    {
        if (count($this->primaryKeys) != count($this->primaryValues)) {
            throw new Exception('Error: The number of primary keys and primary values do not match.');
        } else {
            return true;
        }
    }

    /**
     * Set the columns
     *
     * @param  array $columns
     * @return Row
     */
    public function setColumns(array $columns = []): Row
    {
        $this->columns = $columns;
        if (count($this->primaryValues) == 0) {
            foreach ($this->primaryKeys as $primaryKey) {
                if (isset($this->columns[$primaryKey])) {
                    $this->primaryValues[] = $this->columns[$primaryKey];
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
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Check if row data is dirty
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return ($this->dirty['old'] !== $this->dirty['new']);
    }

    /**
     * Get dirty columns
     *
     * @return array
     */
    public function getDirty(): array
    {
        return $this->dirty;
    }

    /**
     * Reset dirty columns
     *
     * @return Row
     */
    public function resetDirty(): Row
    {
        $this->dirty['old'] = [];
        $this->dirty['new'] = [];
        return $this;
    }

    /**
     * Find row by primary key values
     *
     * @param  mixed  $values
     * @param  array  $selectColumns
     * @param  ?array $options
     * @throws Exception|\Pop\Db\Exception
     * @return array
     */
    public function find(mixed $values, array $selectColumns = [], ?array $options = null): array
    {
        if (count($this->primaryKeys) == 0) {
            throw new Exception('Error: The primary key(s) have not been set.');
        }

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        $this->setPrimaryValues($values);
        $this->doesPrimaryCountMatch();

        if (!empty($selectColumns)) {
            $select = [];
            foreach ($selectColumns as $selectColumn) {
                $select[] = $this->table . '.' . $selectColumn;
            }
        } else if (($options !== null) && !empty($options['select'])) {
            $select = $options['select'];
        } else {
            $select = [$this->table . '.*'];
        }

        $sql->select($select)->from($this->table);

        $params = [];

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }

            if ($this->primaryValues[$i] === null) {
                $sql->select()->where->isNull($this->table . '.' . $primaryKey);
            } else {
                $sql->select()->where->equalTo($this->table . '.' . $primaryKey, $placeholder);
                $params[$primaryKey] = $this->primaryValues[$i];
            }
        }

        if (($options !== null) && isset($options['offset'])) {
            $sql->select()->offset((int)$options['offset']);
        }

        if (($options !== null) && isset($options['join'])) {
            $joins = (is_array($options['join']) && isset($options['join']['table'])) ?
                [$options['join']] : $options['join'];

            foreach ($joins as $join) {
                if (isset($join['type']) && method_exists($sql->select(), $join['type'])) {
                    $joinMethod = $join['type'];
                    $sql->select()->{$joinMethod}($join['table'], $join['columns']);
                } else {
                    $sql->select()->leftJoin($join['table'], $join['columns']);
                }
            }
        }

        $sql->select()->limit(1);

        $db->prepare((string)$sql);
        if (!empty($params)) {
            $db->bindParams($params);
        }
        $db->execute();

        $row = $db->fetch();

        if (($row !== false) && is_array($row)) {
            $this->columns = $row;
        }

        return $this->columns;
    }

    /**
     * Save a new row in the table
     *
     * @param  array $columns
     * @return Row
     */
    public function save(array $columns = []): Row
    {
        $db     = Db::getDb($this->table);
        $sql    = $db->createSql();
        $values = [];
        $params = [];

        if (!empty($columns)) {
            $this->setColumns($columns);
        }

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

        $db->prepare((string)$sql);
        if (!empty($params)) {
            $db->bindParams($params);
        }
        $db->execute();

        // Set the new ID created by the insert
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
     * @throws Exception|\Pop\Db\Exception
     * @return Row
     */
    public function update(): Row
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
            if (!in_array($column, $this->primaryKeys) &&
                ((empty($columnNames)) || (!empty($columnNames) && in_array($column, $columnNames)))) {
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
                if ($this->primaryValues[$key] === null) {
                    $sql->update()->where->isNull($primaryKey);
                } else {
                    $sql->update()->where->equalTo($primaryKey, $placeholder);
                }
            }

            if (array_key_exists($key, $this->primaryValues)) {
                if ($this->primaryValues[$key] !== null) {
                    $params[$this->primaryKeys[$key]] = $this->primaryValues[$key];
                    $values[$this->primaryKeys[$key]] = $placeholder;
                }
            } else if (array_key_exists($this->primaryKeys[$key], $this->columns)) {
                if ($this->primaryValues[$key] !== null) {
                    if (str_starts_with($placeholder, ':')) {
                        $params[$this->primaryKeys[$key]] = $this->columns[$this->primaryKeys[$key]];
                        $values[$this->primaryKeys[$key]] = $placeholder;
                    } else {
                        $params[$key] = $this->columns[$this->primaryKeys[$key]];
                        $values[$key] = $placeholder;
                    }
                }
            } else {
                throw new Exception("Error: The value of '" . $key . "' is not set");
            }
            $i++;
        }

        $db->prepare((string)$sql);
        if (!empty($params)) {
            $db->bindParams($params);
        }
        $db->execute();

        return $this;
    }

    /**
     * Delete row from the table using the primary key(s)
     *
     * @throws Exception|\Pop\Db\Exception
     * @return Row
     */
    public function delete(): Row
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
            if ($this->primaryValues[$i] === null) {
                $sql->delete()->where->isNull($primaryKey);
            } else {
                $sql->delete()->where->equalTo($primaryKey, $placeholder);
                $params[$primaryKey] = $this->primaryValues[$i];
            }
        }

        $db->prepare((string)$sql);
        if (!empty($params)) {
            $db->bindParams($params);
        }
        $db->execute();

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
    public function count(): int
    {
        return count($this->columns);
    }

    /**
     * Method to iterate over the columns
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->columns);
    }

    /**
     * Method to convert row gateway to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->columns;
    }

    /**
     * Magic method to set the property to the value of $this->columns[$name].
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (!isset($this->dirty['old'][$name])) {
            if (array_key_exists($name, $this->columns) && ($value !== $this->columns[$name])) {
                $this->dirty['old'][$name] = $this->columns[$name];
                $this->dirty['new'][$name] = $value;
            } else if (!isset($this->columns[$name]) && isset($value)) {
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
    public function __get(string $name): mixed
    {
        return (isset($this->columns[$name])) ? $this->columns[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->columns[$name].
     *
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * Magic method to unset $this->columns[$name].
     *
     * @param  string $name
     * @return void
     */
    public function __unset(string $name): void
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
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
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
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

}