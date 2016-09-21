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
namespace Pop\Db;

/**
 * Sql class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Sql
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
     * Constructor
     *
     * Instantiate the SQL object
     *
     * @param  Adapter\AbstractAdapter $db
     */
    public function __construct(Adapter\AbstractAdapter $db)
    {
        $this->db = $db;
    }

    /**
     * Access the select object
     *
     * @param  array $values
     * @return Sql\Select
     */
    public function select(array $values = null)
    {
        if (null === $this->select) {
            $this->select = new Sql\Select($this->db);
        }
        if (null !== $values) {
            $this->select->setValues($values);
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
        if (null === $this->delete) {
            $this->delete = new Sql\Delete($this->db);
        }
        if (null !== $table) {
            $this->delete->setTable($table);
        }

        return $this->delete;
    }

}