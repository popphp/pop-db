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
namespace Pop\Db;

use Pop\Db\Sql\AbstractSql;

/**
 * Sql class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Sql extends AbstractSql
{

    /**
     * Database object
     * @var Adapter\AbstractAdapter
     */
    protected $db = null;

    /**
     * Select object
     * @var Sql\Select
     */
    protected $select = null;

    /**
     * Insert object
     * @var Sql\Insert
     */
    protected $insert = null;

    /**
     * Update object
     * @var Sql\Update
     */
    protected $update = null;

    /**
     * Delete object
     * @var Sql\Delete
     */
    protected $delete = null;

    /**
     * Access the select object
     *
     * @param  array $columns
     * @return Sql\Select
     */
    public function select(array $columns = null)
    {
        $this->insert = null;
        $this->update = null;
        $this->delete = null;

        if (null === $this->select) {
            $this->select = new Sql\Select($this->db);
        }
        if (null !== $columns) {
            foreach ($columns as $name => $value) {
                if (!is_numeric($name)) {
                    $this->select->addNamedValue($name, $value);
                } else {
                    $this->select->addValue($value);
                }
            }
        }

        return $this->select;
    }

    /**
     * Access the insert object
     *
     * @param  string $table
     * @return Sql\Insert
     */
    public function insert($table = null)
    {
        $this->select = null;
        $this->update = null;
        $this->delete = null;

        if (null === $this->insert) {
            $this->insert = new Sql\Insert($this->db);
        }
        if (null !== $table) {
            $this->insert->setTable($table);
        }

        return $this->insert;
    }

    /**
     * Access the update object
     *
     * @param  string $table
     * @return Sql\Update
     */
    public function update($table = null)
    {
        $this->insert = null;
        $this->select = null;
        $this->delete = null;

        if (null === $this->update) {
            $this->update = new Sql\Update($this->db);
        }
        if (null !== $table) {
            $this->update->setTable($table);
        }

        return $this->update;
    }

    /**
     * Access the delete object
     *
     * @param  string $table
     * @return Sql\Delete
     */
    public function delete($table = null)
    {
        $this->insert = null;
        $this->update = null;
        $this->select = null;

        if (null === $this->delete) {
            $this->delete = new Sql\Delete($this->db);
        }
        if (null !== $table) {
            $this->delete->setTable($table);
        }

        return $this->delete;
    }

    /**
     * Determine if SQL object has a select object
     *
     * @return boolean
     */
    public function hasSelect()
    {
        return (null !== $this->select);
    }

    /**
     * Determine if SQL object has a insert object
     *
     * @return boolean
     */
    public function hasInsert()
    {
        return (null !== $this->insert);
    }

    /**
     * Determine if SQL object has a update object
     *
     * @return boolean
     */
    public function hasUpdate()
    {
        return (null !== $this->update);
    }

    /**
     * Determine if SQL object has a delete object
     *
     * @return boolean
     */
    public function hasDelete()
    {
        return (null !== $this->delete);
    }

    /**
     * Reset and clear the SQL object
     *
     * @return Sql
     */
    public function reset()
    {
        $this->select = null;
        $this->insert = null;
        $this->update = null;
        $this->delete = null;

        return $this;
    }

    /**
     * Render the SQL statement
     *
     * @return string
     */
    public function render()
    {
        $sql = null;

        if (null !== $this->select) {
            $sql = $this->select->render();
        } else if (null !== $this->insert) {
            $sql = $this->insert->render();
        } else if (null !== $this->update) {
            $sql = $this->update->render();
        } else if (null !== $this->delete) {
            $sql = $this->delete->render();
        }

        $this->reset();

        return $sql;
    }

    /**
     * Render the SQL statement
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}