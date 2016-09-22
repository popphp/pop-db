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
class Result implements \ArrayAccess
{

    /**
     * Data set result constants
     * @var string
     */
    const AS_ARRAY  = 'AS_ARRAY';
    const AS_OBJECT = 'AS_OBJECT';
    const AS_RESULT = 'AS_RESULT';

    /**
     * Database connection
     * @var AbstractAdapter
     */
    protected $db = null;

    /**
     * SQL Object
     * @var Sql
     */
    protected $sql = null;

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Result rows
     * @var array
     */
    protected $rows = [];

    /**
     * Columns of the first result row
     * @var string
     */
    protected $columns = [];

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
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $this->db           = $db;
        $this->sql          = $db->createSql();
        $this->primaryKeys  = $keys;
        $this->table        = $table;
        $this->rowGateway   = new Gateway\Row($this->sql, $table, $this->primaryKeys);
        $this->tableGateway = new Gateway\Table($this->sql, $table);


        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
        }
    }

    /**
     * Find record by ID method
     *
     * @param  mixed  $id
     * @param  string $resultsAs
     * @return Result
     */
    public function findById($id, $resultsAs = 'AS_RESULT')
    {
        $this->setColumns($this->rowGateway->find($id), $resultsAs);
        return $this;
    }

    /**
     * Find records by method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultsAs
     * @return Result
     */
    public function findBy(array $columns = null, array $options = null, $resultsAs = 'AS_RESULT')
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $this->setRows($this->tableGateway->select(null, $where, $params, $options), $resultsAs);

        return $this;
    }

    /**
     * Find all records method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return Result
     */
    public function findAll(array $options = null, $resultsAs = 'AS_RESULT')
    {
        return $this->findBy(null, $options, $resultsAs);
    }

    /**
     * Method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $resultsAs
     * @return Result
     */
    public function execute($sql, $params, $resultsAs = 'AS_RESULT')
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
            $this->setRows($rows, $resultsAs);
        }

        return $this;
    }

    /**
     * Method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultsAs
     * @return Result
     */
    public function query($sql, $resultsAs = 'AS_RESULT')
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
            $this->setRows($rows, $resultsAs);
        }

        return $this;
    }

    /**
     * Save the record
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return void
     */
    public function save(array $columns = null, $resultsAs = 'AS_RESULT')
    {
        // Save or update the record
        if (null === $columns) {
            $this->rowGateway->setColumns($this->columns);
            if ($this->isNew) {
                $this->rowGateway->save();
            } else {
                $this->rowGateway->update();
            }
            $this->setRows([$this->rowGateway->getColumns()], $resultsAs);
        // Else, save multiple rows
        } else {
            $this->tableGateway->insert($columns);
            $this->setRows($this->tableGateway->getRows(), $resultsAs);
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
            $this->setColumns();
            if (isset($this->rows[0])) {
                unset($this->rows[0]);
            }
            // Delete multiple rows
        } else {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $this->tableGateway->delete($parsedColumns['where'], $parsedColumns['params']);
            $this->setRows();
        }
    }

    /**
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return int
     */
    public function getTotal(array $columns = null, $resultsAs = 'AS_RESULT')
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $this->setRows($this->tableGateway->select(['total_count' => 'COUNT(1)'], $where, $params), $resultsAs);

        return (int)$this->total_count;
    }

    /**
     * Get table info and return as an array
     *
     * @return array
     */
    public function getTableInfo()
    {
        return $this->rowGateway->getTableInfo();
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @param  string $resultsAs
     * @throws Exception
     * @return Result
     */
    public function setColumns($columns = null, $resultsAs = 'AS_RESULT')
    {
        $this->columns = [];
        $this->rows    = [];

        if (null !== $columns) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->columns = (array)$columns;
                switch ($resultsAs) {
                    case self::AS_ARRAY:
                        $this->rows[0] = $this->columns;
                        break;
                    case self::AS_OBJECT:
                        $this->rows[0] = new \ArrayObject($this->columns, \ArrayObject::ARRAY_AS_PROPS);
                        break;
                    default:
                        $this->rows[0] = $this;
                }
            } else {
                throw new Exception('The parameter passed must be either an array, an array object or null.');
            }
        }

        return $this;
    }

    /**
     * Set all the table rows at once
     *
     * @param  array  $rows
     * @param  string $resultsAs
     * @return Result
     */
    public function setRows(array $rows = null, $resultsAs = 'AS_RESULT')
    {
        $this->columns = [];
        $this->rows    = [];

        if (null !== $rows) {
            $this->columns = (isset($rows[0])) ? (array)$rows[0] : [];
            foreach ($rows as $row) {
                switch ($resultsAs) {
                    case self::AS_ARRAY:
                        $this->rows[] = (array)$row;
                        break;
                    case self::AS_OBJECT:
                        $this->rows[] = new \ArrayObject((array)$row, \ArrayObject::ARRAY_AS_PROPS);
                        break;
                    default:
                        $r = new self($this->db, $this->table, $this->primaryKeys);
                        $r->setColumns((array)$row, $resultsAs);
                        $this->rows[] = $r;
                }
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
     * Get the rows
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the rows (alias method)
     *
     * @return array
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Determine if the result has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return (count($this->rows) > 0);
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