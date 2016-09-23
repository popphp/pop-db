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
namespace Pop\Db\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Gateway;
use Pop\Db\Parser;
use Pop\Db\Sql;

/**
 * Result class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Result extends AbstractRecord implements \ArrayAccess
{

    /**
     * Columns of the first result row
     * @var string
     */
    protected $columns = [];

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Is new record flag
     * @var boolean
     */
    protected $isNew = false;

    /**
     * Constructor
     *
     * Instantiate the database record result object
     *
     * @param  AbstractAdapter $db
     * @param  string          $table
     * @param  mixed           $keys
     * @param  array           $columns
     */
    public function __construct(AbstractAdapter $db, $table, $keys, array $columns = null)
    {
        parent::__construct($db, $table);

        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $this->primaryKeys  = $keys;
        $this->rowGateway   = new Gateway\Row($this->sql, $table, $this->primaryKeys);

        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
        }
    }

    /**
     * Find record by ID method
     *
     * @param  mixed $id
     * @return Result
     */
    public function findById($id)
    {
        $this->setColumns($this->rowGateway->find($id));
        return $this;
    }

    /**
     * Method to execute a custom prepared SQL statement.
     *
     * @param  mixed $sql
     * @param  mixed $params
     * @return Result
     */
    public function execute($sql, $params)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }
        if (!is_array($params)) {
            $params = [$params];
        }

        $this->db->prepare($sql)
             ->bindParams($params)
             ->execute();

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $this->db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $row;
            }
            if (isset($rows[0])){
                $this->setColumns($rows[0]);
            }
        }

        return $this;
    }

    /**
     * Method to execute a custom SQL query.
     *
     * @param  mixed $sql
     * @return Result
     */
    public function query($sql)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $this->db->query($sql);

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = [];
            while (($row = $this->db->fetch())) {
                $rows[] = $row;
            }

            if (isset($rows[0])){
                $this->setColumns($rows[0]);
            }
        }

        return $this;
    }

    /**
     * Save the record
     *
     * @param  array $columns
     * @return void
     */
    public function save(array $columns = null)
    {
        // Save or update the record
        if (null === $columns) {
            $this->rowGateway->setColumns($this->columns);
            if ($this->isNew) {
                $this->rowGateway->save();
            } else {
                $this->rowGateway->update();
            }
            $this->setColumns($this->rowGateway->getColumns());
        // Else, save multiple rows
        } else {
            $this->tableGateway->insert($columns);
            $rows = $this->tableGateway->getRows();

            if (isset($rows[0])) {
                $this->setColumns($rows[0]);
            }
        }
    }

    /**
     * Delete the record or rows of records
     *
     * @param  array  $columns
     * @return void
     */
    public function delete(array $columns = null)
    {
        // Delete the record
        if (null === $columns) {
            if ((count($this->columns) > 0) && (count($this->rowGateway->getColumns()) == 0)) {
                $this->rowGateway->setColumns($this->columns);
            }
            $this->rowGateway->delete();
        // Delete multiple rows
        } else {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $this->tableGateway->delete($parsedColumns['where'], $parsedColumns['params']);
        }
        $this->setColumns();
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return Result
     */
    public function setColumns($columns = null)
    {
        $this->columns = [];

        if (null !== $columns) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->columns = (array)$columns;
            } else {
                throw new Exception('The parameter passed must be either an array, an array object or null.');
            }
        }

        return $this;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->columns;
    }

    /**
     * Get column values as array object
     *
     * @return \ArrayObject
     */
    public function toArrayObject()
    {
        return new \ArrayObject($this->columns, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Magic method to set the property to the value of $this->columns[$name]
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
     * Magic method to return the value of $this->columns[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->columns[$name])) ? $this->columns[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->columns[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * Magic method to unset $this->columns[$name]
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
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}