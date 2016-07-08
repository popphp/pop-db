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
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Result implements \ArrayAccess
{

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
     * Result rows (an array of arrays)
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
     * Instantiate the database record result object.
     *
     * @param  AbstractAdapter $db
     * @param  string          $table
     * @param  mixed           $keys
     * @param  array           $columns
     *
     * @throws Exception
     * @return Result
     */
    public function __construct(AbstractAdapter $db, $table, $keys, array $columns = null)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $this->setDb($db);
        $this->setPrimaryKeys($keys);
        $this->setSql(new Sql($db, $table));
        $this->setTable($table);

        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
        }
    }

    /**
     * Check is the result instance has a DB adapter
     *
     * @return boolean
     */
    public function hasDb()
    {
        return ($this->db !== null);
    }

    /**
     * Set DB connection
     *
     * @param  AbstractAdapter $db
     * @return Result
     */
    public function setDb(AbstractAdapter $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * Get the DB adapter
     *
     * @return AbstractAdapter
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get DB adapter (alias)
     *
     * @throws Exception
     * @return AbstractAdapter
     */
    public function db()
    {
        return $this->getDb();
    }

    /**
     * Check is the result instance has a SQL object
     *
     * @return boolean
     */
    public function hasSql()
    {
        return ($this->sql !== null);
    }

    /**
     * Set the SQL object
     *
     * @param  Sql $sql
     * @return Result
     */
    public function setSql(Sql $sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Get the SQL object
     *
     * @return Sql
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the SQL object (alias)
     *
     * @throws Exception
     * @return Sql
     */
    public function sql()
    {
        return $this->getSql();
    }

    /**
     * Find record by ID method
     *
     * @param  mixed  $id
     * @param  string $resultsAs
     * @return Result
     */
    public function findById($id, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        $this->setColumns($this->rg()->find($id), $resultsAs);
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
    public function findBy(array $columns = null, array $options = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->getSql()->getPlaceholder());
            $params = $parsedColumns['params'];
            $where  = $parsedColumns['where'];
        }

        $this->setRows($this->tg()->select(null, $where, $params, $options), $resultsAs);

        return $this;
    }

    /**
     * Find all records method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return Result
     */
    public function findAll(array $options = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
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
    public function execute($sql, $params, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }
        if (!is_array($params)) {
            $params = [$params];
        }

        $db = static::db();
        $db->prepare($sql)
            ->bindParams($params)
            ->execute();

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $db->fetchResult();
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
    public function query($sql, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = static::db();
        $db->query($sql);

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = [];
            while (($row = $db->fetch())) {
                $rows[] = $row;
            }
            $this->setRows($rows, $resultsAs);
        }

        return $this;
    }

    /**
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return int
     */
    public function getTotal(array $columns = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->getSql()->getPlaceholder());
            $params = $parsedColumns['params'];
            $where  = $parsedColumns['where'];
        }

        $this->setRows($this->tg()->select(['total_count' => 'COUNT(1)'], $where, $params), $resultsAs);

        return (int)$this->total_count;
    }

    /**
     * Set all the table column values at once.
     *
     * @param  mixed  $columns
     * @param  string $resultsAs
     * @throws Exception
     * @return Result
     */
    public function setColumns($columns = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        // If null, clear the columns.
        if (null === $columns) {
            $this->columns = [];
            $this->rows    = [];
            // Else, if an array, set the columns.
        } else if (is_array($columns) || ($columns instanceof \ArrayObject)) {
            $this->columns = (array)$columns;
            switch ($resultsAs) {
                case \Pop\Db\Record::ROW_AS_ARRAY:
                    $this->rows[0] = $this->columns;
                    break;
                case \Pop\Db\Record::ROW_AS_ARRAYOBJECT:
                    $this->rows[0] = new \ArrayObject($this->columns, \ArrayObject::ARRAY_AS_PROPS);
                    break;
                default:
                    $this->rows[0] = $this;
            }
        } else {
            throw new Exception('The parameter passed must be either an array or null.');
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
    public function setRows(array $rows = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        // If null, clear the rows.
        if (null === $rows) {
            $this->columns    = [];
            $this->rows       = [];
        } else {
            $this->columns = (isset($rows[0])) ? (array)$rows[0] : [];
            foreach ($rows as $row) {
                switch ($resultsAs) {
                    case \Pop\Db\Record::ROW_AS_ARRAY:
                        $this->rows[] = (array)$row;
                        break;
                    case \Pop\Db\Record::ROW_AS_ARRAYOBJECT:
                        $this->rows[] = new \ArrayObject((array)$row, \ArrayObject::ARRAY_AS_PROPS);
                        break;
                    default:
                        $r = new self($this->db, $this->table, $this->primaryKeys);
                        $r->setColumns((array)$row, $resultsAs);
                        $this->rows[] = $r;
                }
            }
        }
    }

    /**
     * Set the table
     *
     * @param  string $table
     * @return Result
     */
    public function setTable($table)
    {
        $this->table        = $table;
        $this->rowGateway   = new Gateway\Row($this->sql, $this->primaryKeys, $table);
        $this->tableGateway = new Gateway\Table($this->sql, $table);

        return $this;
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return Result
     */
    public function setPrimaryKeys(array $keys)
    {
        $this->primaryKeys = $keys;
        return $this;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get table info and return as an array.
     *
     * @return array
     */
    public function getTableInfo()
    {
        return $this->rg()->getTableInfo();
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
     * Get the columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the columns as a single array object
     *
     * @return \ArrayObject
     */
    public function getColumnsAsObject()
    {
        return new \ArrayObject($this->columns, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Alias for getColumns
     *
     * @return array
     */
    public function toArray()
    {
        return $this->columns;
    }

    /**
     * Alias to getColumnsAsObject
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
     * Save the record
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return void
     */
    public function save(array $columns = null, $resultsAs = \Pop\Db\Record::ROW_AS_RESULT)
    {
        // Save or update the record
        if (null === $columns) {
            $this->rg()->setColumns($this->columns);
            $this->rg()->save($this->isNew);
            $this->setRows([$this->rg()->getColumns()], $resultsAs);
            // Else, save multiple rows
        } else {
            $this->tg()->insert($columns);
            $this->setRows($this->tg()->getRows(), $resultsAs);
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
            if ((count($this->columns) > 0) && (count($this->rg()->getColumns()) == 0)) {
                $this->rg()->setColumns($this->columns);
            }
            $this->rg()->delete();
            $this->setColumns();
            if (isset($this->rows[0])) {
                unset($this->rows[0]);
            }
        // Delete multiple rows
        } else {
            $parsedColumns = Parser\Column::parse($columns, $this->getSql()->getPlaceholder());
            $this->tg()->delete($parsedColumns['where'], $parsedColumns['params']);
            $this->setRows();
        }
    }

    /**
     * Get the row gateway object
     *
     * @return Gateway\Row
     */
    protected function rg()
    {
        return $this->rowGateway;
    }

    /**
     * Get the table gateway object
     *
     * @return Gateway\Table
     */
    protected function tg()
    {
        return $this->tableGateway;
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