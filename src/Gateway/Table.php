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

        $this->sql->select($columns)->from($this->table);

        if (null !== $where) {
            $this->sql->select()->where->add($where);
        }

        if (isset($options['limit'])) {
            $this->sql->select()->limit((int)$options['limit']);
        }

        if (isset($options['offset'])) {
            $this->sql->select()->offset((int)$options['offset']);
        }

        if (isset($options['order'])) {
            $order = Parser\Order::parse($options['order']);
            $this->sql->select()->orderBy($order['by'], $this->sql->db()->escape($order['order']));
        }

        $this->sql->db()->prepare((string)$this->sql);
        if ((null !== $parameters) && (count($parameters) > 0)) {
            $this->sql->db()->bindParams($parameters);
        }

        $this->sql->db()->execute();

        $this->rows = $this->sql->db()->fetchAll();

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

        $values = [];
        $params = [];

        $i = 1;
        foreach ($columns as $column => $value) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= $i;
            }
            $values[$column] = $placeholder;
            $params[]        = $value;
            $i++;
        }

        $this->sql->insert($this->table)->values($values);

        $this->sql->db()->prepare((string)$this->sql)
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

        $values = [];
        $params = [];

        $i = 1;
        foreach ($columns as $column => $value) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $column;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $values[$column] = $placeholder;
            $params[$column] = $value;
            $i++;
        }

        $this->sql->update($this->table)->values($values);

        if (null !== $where) {
            $this->sql->update()->where->add($where);
        }

        $this->sql->db()->prepare((string)$this->sql)
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

        $this->sql->delete($this->table);

        if (null !== $where) {
            $this->sql->delete()->where->add($where);
        }

        $this->sql->db()->prepare((string)$this->sql);

        if (count($parameters) > 0) {
            $this->sql->db()->bindParams($parameters);
        }

        $this->sql->db()->execute();

        return $this;
    }

}