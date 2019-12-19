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
namespace Pop\Db\Gateway;

use Pop\Db\Db;
use Pop\Db\Sql\Parser;

/**
 * Table gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Table extends AbstractGateway implements \Countable, \IteratorAggregate
{

    /**
     * Result rows
     * @var array
     */
    protected $rows = [];

    /**
     * Get the number of result rows
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        return count($this->rows);
    }

    /**
     * Get the result rows
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return (count($this->rows) > 0);
    }

    /**
     * Get the result rows (alias method)
     *
     * @return array
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Method to convert table gateway to an array (alias method)
     *
     * @return array
     */
    public function toArray()
    {
        return $this->rows;
    }

    /**
     * Select rows from the table
     *
     * @param  array $columns
     * @param  mixed $where
     * @param  array $parameters
     * @param  array $options
     * @return array
     */
    public function select(array $columns = null, $where = null, array $parameters = null, array $options = null)
    {
        $this->rows = [];

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        if (null === $columns) {
            $columns = [$this->table . '.*'];
        }

        $sql->select($columns)->from($this->table);

        if (null !== $where) {
            $sql->select()->where($where);
        }

        if (isset($options['limit'])) {
            $sql->select()->limit((int)$options['limit']);
        }

        if (isset($options['offset'])) {
            $sql->select()->offset((int)$options['offset']);
        }

        if (isset($options['join'])) {
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

        if (isset($options['order'])) {
            if (!is_array($options['order'])) {
                $orders = (strpos($options['order'], ',') !== false) ?
                    explode(',', $options['order']) : [$options['order']];
            } else {
                $orders = $options['order'];
            }
            foreach ($orders as $order) {
                $ord = Parser\Order::parse(trim($order));
                $sql->select()->orderBy($ord['by'], $db->escape($ord['order']));
            }
        }

        $db->prepare((string)$sql);

        if ((null !== $parameters) && (count($parameters) > 0)) {
            $db->bindParams($parameters);
        }

        $db->execute();

        $this->rows = $db->fetchAll();

        return $this->rows;
    }

    /**
     * Insert a row of values into the table
     *
     * @param  array $columns
     * @return Table
     */
    public function insert(array $columns)
    {
        $this->rows = [];

        $db     = Db::getDb($this->table);
        $sql    = $db->createSql();
        $values = [];
        $params = [];
        $i      = 1;

        foreach ($columns as $column => $value) {
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

        return $this;
    }

    /**
     * Insert rows of values into the table
     *
     * @param  array $values
     * @return Table
     */
    public function insertRows($values)
    {
        $this->rows   = [];
        $db           = Db::getDb($this->table);
        $sql          = $db->createSql();
        $placeholders = [];
        $columns      = array_keys($values[0]);

        foreach ($columns as $i => $column) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $placeholders[$column] = $placeholder;
        }

        $sql->insert($this->table)->values($placeholders);
        $db->prepare((string)$sql);

        foreach ($values as $rowValues) {
            $db->bindParams($rowValues)->execute();
        }

        return $this;
    }

    /**
     * Update a table
     *
     * @param  array $columns
     * @param  mixed $where
     * @param  array $parameters
     * @return Table
     */
    public function update(array $columns, $where = null, array $parameters = [])
    {
        $this->rows = [];

        $db     = Db::getDb($this->table);
        $sql    = $db->createSql();
        $values = [];
        $params = [];
        $i      = 1;

        foreach ($columns as $column => $value) {
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

        $sql->update($this->table)->values($values);

        if (null !== $where) {
            $sql->update()->where($where);
        }

        $db->prepare((string)$sql)
           ->bindParams($params + $parameters)
           ->execute();

        return $this;
    }

    /**
     * Delete from a table
     *
     * @param  mixed $where
     * @param  array $parameters
     * @return Table
     */
    public function delete($where = null, array $parameters = [])
    {
        $this->rows = [];

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        $sql->delete($this->table);

        if (null !== $where) {
            $sql->delete()->where($where);
        }

        $db->prepare((string)$sql);

        if (count($parameters) > 0) {
            $db->bindParams($parameters);
        }

        $db->execute();

        return $this;
    }

    /**
     * Set all the table rows at once
     *
     * @param  array  $rows
     * @return Table
     */
    public function setRows(array $rows = [])
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Method to get the count of items in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Method to iterate over the table rows
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->rows);
    }

}