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

use Pop\Db\Record\Result;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Record implements \ArrayAccess
{

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = null;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Foreign keys
     * @var array
     */
    protected $foreignKeys = [];

    /**
     * Record result object
     * @var Record\Result
     */
    protected $result = null;

    /**
     * 1:1 associations
     * @var array
     */
    protected $oneToOne = [];

    /**
     * 1:Many associations
     * @var array
     */
    protected $oneToMany = [];

    /**
     * Constructor
     *
     * Instantiate the database record object
     *
     * Optional parameters are an array of column values, db adapter,
     * or a table name
     *
     * @throws Exception
     */
    public function __construct()
    {
        $args    = func_get_args();
        $columns = null;
        $table   = null;
        $db      = null;
        $class   = get_class($this);

        foreach ($args as $arg) {
            if (is_array($arg) || ($arg instanceof \ArrayAccess) || ($arg instanceof \ArrayObject)) {
                $columns = $arg;
            } else if ($arg instanceof Adapter\AbstractAdapter) {
                $db      = $arg;
            } else if (is_string($arg)) {
                $table   = $arg;
            }
        }

        if (null !== $db) {
            $isDefault = ($class === __CLASS__);
            Db::setDb($db, $class, null, $isDefault);
        }

        if (!Db::hasDb($class)) {
            throw new Exception('Error: A database connection has not been set.');
        }

        if (null !== $table) {
            $this->setTable($table);
        }

        // Set the table name from the class name
        if (null === $this->table) {
            $this->setTableFromClassName($class);
        }

        $this->result = new Result($this->getFullTable(), $this->primaryKeys, $columns);
        $this->result->setForeignKeys($this->foreignKeys)
             ->setOneToOne($this->oneToOne)
             ->setOneToMany($this->oneToMany);
    }

    /**
     * Check for a DB adapter
     *
     * @return boolean
     */
    public static function hasDb()
    {
        return Db::hasDb(get_called_class());
    }

    /**
     * Set DB adapter
     *
     * @param  Adapter\AbstractAdapter $db
     * @param  string                  $prefix
     * @param  boolean                 $isDefault
     * @return void
     */
    public static function setDb(Adapter\AbstractAdapter $db, $prefix = null, $isDefault = false)
    {
        $class = get_called_class();
        if ($class == 'Pop\Db\Record') {
            Db::setDefaultDb($db);
        } else {
            Db::setDb($db, $class, $prefix, $isDefault);
        }
    }

    /**
     * Set DB adapter
     *
     * @param  Adapter\AbstractAdapter $db
     * @return void
     */
    public static function setDefaultDb(Adapter\AbstractAdapter $db)
    {
        Db::setDb($db, null, null, true);
    }

    /**
     * Get DB adapter
     *
     * @return Adapter\AbstractAdapter
     */
    public static function getDb()
    {
        return Db::getDb(get_called_class());
    }

    /**
     * Get DB adapter (alias)
     *
     * @return Adapter\AbstractAdapter
     */
    public static function db()
    {
        return Db::db(get_called_class());
    }

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @return mixed
     */
    public static function findById($id)
    {
        return (new static())->getResult()->findById($id);
    }

    /**
     * Find by static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultsAs
     * @return mixed
     */
    public static function findBy(array $columns = null, array $options = null, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->findBy($columns, $options, $resultsAs);
    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return mixed
     */
    public static function findAll(array $options = null, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->findBy(null, $options, $resultsAs);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $resultsAs
     * @return mixed
     */
    public static function execute($sql, $params, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->execute($sql, $params, $resultsAs);
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultsAs
     * @return mixed
     */
    public static function query($sql, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->query($sql, $resultsAs);
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return int
     */
    public static function getTotal(array $columns = null, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->getTotal($columns, $resultsAs);
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @return array
     */
    public static function getTableInfo()
    {
        return (new static())->getResult()->getTableInfo();
    }

    /**
     * Save the record
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return void
     */
    public function save(array $columns = null, $resultsAs = Result::AS_OBJECT)
    {
        if (null !== $this->result) {
            $this->result->save($columns, $resultsAs);
        }
    }

    /**
     * Delete the record
     *
     * @param  array $columns
     * @return void
     */
    public function delete(array $columns = null)
    {
        if (null !== $this->result) {
            $this->result->delete($columns);
        }
    }

    /**
     * Set the table
     *
     * @param  string $table
     * @return Record
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table from a class name
     *
     * @param  string $class
     * @return Record
     */
    public function setTableFromClassName($class)
    {
        if (strpos($class, '_') !== false) {
            $cls = substr($class, (strrpos($class, '_') + 1));
        } else if (strpos($class, '\\') !== false) {
            $cls = substr($class, (strrpos($class, '\\') + 1));
        } else {
            $cls = $class;
        }
        return $this->setTable(Parser\Table::parse($cls));
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return Record
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return Record
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
     * Get the full table name (prefix + table)
     *
     * @return string
     */
    public function getFullTable()
    {
        return $this->prefix . $this->table;
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
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
     * Get the foreign keys
     *
     * @return array
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Get the record result object
     *
     * @return Record\Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Magic method to set the property to the value of $this->result[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (null !== $this->result) {
            $this->result[$name] = $value;
        }
    }

    /**
     * Magic method to return the value of $this->result[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ((null !== $this->result) && isset($this->result[$name])) ? $this->result[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->result[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->result[$name]);
    }

    /**
     * Magic method to unset $this->result[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->result[$name])) {
            unset($this->result[$name]);
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