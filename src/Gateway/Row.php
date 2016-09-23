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
namespace Pop\Db\Gateway;

use Pop\Db\Sql;

/**
 * Row gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Row extends AbstractGateway implements \ArrayAccess
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
     * Constructor
     *
     * Instantiate the row gateway object.
     *
     * @param  Sql    $sql
     * @param  string $table
     * @param  mixed  $primaryKeys
     */
    public function __construct(Sql $sql, $table, $primaryKeys = null)
    {
        if (null !== $primaryKeys) {
            $this->setPrimaryKeys($primaryKeys);
        }
        parent::__construct($sql, $table);
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
        return $this->primaryKeys;
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
            throw new Exception('Error: The number of primary key(s) and primary value(s) do not match.');
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

        $this->setPrimaryValues($values);
        $this->doesPrimaryCountMatch();

        $this->sql->select([$this->table . '.*'])->from($this->table);

        $params = [];

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $this->sql->select()->where->equalTo($primaryKey, $placeholder);
            $params[$primaryKey] = $this->primaryValues[$i];
        }

        if (count($this->oneToOne) > 0) {
            foreach ($this->oneToOne as $oneToOne) {
                $columns = (isset($oneToOne['columns'])) ? $oneToOne['columns'] : [$oneToOne['table'] . '.*'];
                $this->sql->select($columns)->join($oneToOne['table'], $oneToOne['on'], $oneToOne['join']);
            }
        }

        $this->sql->select()->limit(1);

        $this->sql->db()->prepare((string)$this->sql)
             ->bindParams($params)
             ->execute();

        $row = $this->sql->db()->fetch();

        if (($row !== false) && is_array($row)) {
            if (count ($this->oneToMany) > 0) {
                foreach ($this->oneToMany as $entity => $oneToMany) {
                    $this->sql->reset();
                    $this->sql->select()->from($oneToMany['table']);

                    $params  = [];
                    $columns = (is_array($oneToMany['on'])) ? $oneToMany['on'] : [$oneToMany['on']];

                    $i = 0;
                    foreach ($columns as $foreignColumn => $key) {
                        if (strpos($foreignColumn, '.') !== false) {
                            $foreignColumn = substr($foreignColumn, (strrpos($foreignColumn, '.') + 1));
                        }
                        $placeholder = $this->sql->getPlaceholder();

                        if ($placeholder == ':') {
                            $placeholder .= $foreignColumn;
                        } else if ($placeholder == '$') {
                            $placeholder .= ($i + 1);
                        }
                        $this->sql->select()->where->equalTo($foreignColumn, $placeholder);
                        $params[$key] = $this->primaryValues[$i];
                        $i++;
                    }

                    $this->sql->db()->prepare((string)$this->sql)
                         ->bindParams($params)
                         ->execute();

                    $row[$entity] = $this->sql->db()->fetchAll();
                }
            }

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
        $values = [];
        $params = [];

        $i = 1;
        foreach ($this->columns as $column => $value) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }
            $values[$column] = $placeholder;
            $params[$column]  = $value;
            $i++;
        }

        $this->sql->insert($this->table)->values($values);

        $this->sql->db()->prepare((string)$this->sql)
            ->bindParams($params)
            ->execute();

        if ((count($this->primaryKeys) == 1) && !isset($this->columns[$this->primaryKeys[0]])) {
            $this->columns[$this->primaryKeys[0]] = $this->sql->db()->getLastId();
        }

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
        $values = [];
        $params = [];

        $i = 1;
        foreach ($this->columns as $column => $value) {
            if (!in_array($column, $this->primaryKeys)) {
                $placeholder = $this->sql->getPlaceholder();

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

        $this->sql->update($this->table)->values($values);

        foreach ($this->primaryKeys as $key => $primaryKey) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }
            $this->sql->update()->where->equalTo($primaryKey, $placeholder);
            if (isset($this->primaryValues[$key])) {
                if (substr($placeholder, 0 , 1) == ':') {
                    $params[$this->primaryKeys[$key]] = $this->primaryValues[$key];
                } else {
                    $params[$key] = $this->primaryValues[$key];
                }
            } else if (isset($this->columns[$this->primaryKeys[$key]])) {
                if (substr($placeholder, 0 , 1) == ':') {
                    $params[$this->primaryKeys[$key]] = $this->columns[$this->primaryKeys[$key]];
                } else {
                    $params[$key] = $this->columns[$this->primaryKeys[$key]];
                }

            } else {
                throw new Exception('Error: The value of \'' . $key . '\' is not set');
            }
            $i++;
        }

        $this->sql->db()->prepare((string)$this->sql)
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

        $this->doesPrimaryCountMatch();

        $this->sql->delete($this->table);

        $params = [];
        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $this->sql->delete()->where->equalTo($primaryKey, $placeholder);
            $params[$primaryKey] = $this->primaryValues[$i];
        }

        $this->sql->db()->prepare((string)$this->sql)
             ->bindParams($params)
             ->execute();

        $this->columns       = [];
        $this->primaryValues = [];

        return $this;
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