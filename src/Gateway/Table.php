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

use Pop\Db\Db;
use Pop\Db\Parser;

/**
 * Table gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Table extends AbstractGateway
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
     * Get the result rows (alias method)
     *
     * @return array
     */
    public function rows()
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

        if (null === $columns) {
            $columns = [$this->table . '.*'];
        }

        $db  = Db::getDb($this->table);
        $sql = $db->createSql();

        $sql->select($columns)->from($this->table);

        if (null !== $where) {
            $sql->select()->where->add($where);
        }

        if (count($this->oneToOne) > 0) {
            foreach ($this->oneToOne as $table => $columns) {
                $sql->select([$table . '.*'])->leftJoin($table, $columns);
            }
        }

        if (isset($options['limit'])) {
            $sql->select()->limit((int)$options['limit']);
        }

        if (isset($options['offset'])) {
            $sql->select()->offset((int)$options['offset']);
        }

        if (isset($options['order'])) {
            $order = Parser\Order::parse($options['order']);
            $sql->select()->orderBy($order['by'], $db->escape($order['order']));
        }

        $db->prepare((string)$sql);
        if ((null !== $parameters) && (count($parameters) > 0)) {
            $db->bindParams($parameters);
        }

        $db->execute();

        $this->rows = $db->fetchAll();

        if (count ($this->oneToMany) > 0) {
            foreach ($this->rows as $index => $row) {
                foreach ($this->oneToMany as $entity => $oneToMany) {
                    $table        = key($oneToMany);
                    $column       = array_values($oneToMany)[0];
                    $foreignTable = substr($table, 0, (strrpos($table, '.')));
                    $foreignKey   = substr($table, (strrpos($table, '.') + 1));
                    $primaryKey   = substr($column, (strrpos($column, '.') + 1));

                    $sql->reset();
                    $sql->select()->from($foreignTable);

                    $placeholder = $sql->getPlaceholder();

                    if ($placeholder == ':') {
                        $placeholder .= $foreignKey;
                    } else if ($placeholder == '$') {
                        $placeholder .= 1;
                    }
                    $sql->select()->where->equalTo($foreignKey, $placeholder);
                    $params = [$foreignKey => $row[$primaryKey]];

                    $db->prepare((string)$sql)
                       ->bindParams($params)
                       ->execute();

                    $this->rows[$index][$entity] = $db->fetchAll();
                }
            }
        }

        return $this->rows;
    }

    /**
     * Insert values into the table
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

        $i = 1;
        foreach ($columns as $column => $value) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }
            $values[$column] = $placeholder;
            $params[]        = $value;
            $i++;
        }

        $sql->insert($this->table)->values($values);

        $db->prepare((string)$sql)
           ->bindParams($params)
           ->execute();

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

        $i = 1;
        foreach ($columns as $column => $value) {
            $placeholder = $sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $values[$column] = $placeholder;
            $params[$column] = $value;
            $i++;
        }

        $sql->update($this->table)->values($values);

        if (null !== $where) {
            $sql->update()->where->add($where);
        }

        $db->prepare((string)$sql)
           ->bindParams(array_merge($params, $parameters))
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
            $sql->delete()->where->add($where);
        }

        $db->prepare((string)$sql);

        if (count($parameters) > 0) {
            $db->bindParams($parameters);
        }

        $db->execute();

        return $this;
    }

}