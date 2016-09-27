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
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Record extends Record\AbstractRecord implements \ArrayAccess
{

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

        $this->tableGateway = new Gateway\Table($this->getFullTable());
        $this->rowGateway   = new Gateway\Row($this->getFullTable(), $this->primaryKeys);
        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
            $this->setRows([$columns]);
        }
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
     * @return Record
     */
    public static function findById($id)
    {
        $record = new static();
        $row    = $record->getRowGateway()->find($id);
        $record->setColumns($row);
        $record->setRows([$row]);

        return $record;
    }

    /**
     * Find by static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $rowsAs
     * @return Record
     */
    public static function findBy(array $columns = null, array $options = null, $rowsAs = Record::AS_OBJECT)
    {
        $record = new static();
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $db  = Db::getDb($record->getFullTable());
            $sql = $db->createSql();

            $parsedColumns = Parser\Column::parse($columns, $sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows = $record->getTableGateway()->select(null, $where, $params, $options);
        $record->setRows($rows, $rowsAs);
        if (isset($rows[0])) {
            $record->setColumns($rows[0]);
        }

        return $record;
    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @param  string $rowsAs
     * @return Record
     */
    public static function findAll(array $options = null, $rowsAs = Record::AS_OBJECT)
    {
        return static::findBy(null, $options, $rowsAs);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $rowsAs
     * @return mixed
     */
    public static function execute($sql, $params, $rowsAs = Record::AS_OBJECT)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        if (!is_array($params)) {
            $params = [$params];
        }

        $db = Db::getDb($record->getFullTable());

        $db->prepare($sql)
           ->bindParams($params)
           ->execute();

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $row;
            }
            $record->setRows($rows, $rowsAs);
            if (isset($rows[0])){
                $record->setColumns($rows[0]);
            }
        }

        return $record;
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $rowsAs
     * @return Record
     */
    public static function query($sql, $rowsAs = Record::AS_OBJECT)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = Db::getDb($record->getFullTable());

        $db->query($sql);

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = [];
            while (($row = $db->fetch())) {
                $rows[] = $row;
            }
            $record->setRows($rows, $rowsAs);
            if (isset($rows[0])){
                $record->setColumns($rows[0]);
            }
        }

        return $record;
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $rowsAs
     * @return int
     */
    public static function getTotal(array $columns = null, $rowsAs = Record::AS_OBJECT)
    {
        return (new static())->getTotalCount($columns, $rowsAs);
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @return array
     */
    public static function getTableInfo()
    {
        return (new static())->getTableGateway()->getTableInfo();
    }

    /**
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @return int
     */
    public function getTotalCount(array $columns = null)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $db  = Db::getDb($this->getFullTable());
            $sql = $db->createSql();

            $parsedColumns = Parser\Column::parse($columns, $sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows = $this->tableGateway->select(['total_count' => 'COUNT(1)'], $where, $params);

        return (isset($rows[0]) && isset($rows[0]['total_count'])) ? (int)$rows[0]['total_count'] : 0;
    }

    /**
     * Save the record
     *
     * @param  array  $columns
     * @param  string $rowsAs
     * @return void
     */
    public function save(array $columns = null, $rowsAs = Record::AS_OBJECT)
    {
        // Save or update the record
        if (null === $columns) {
            if ($this->isNew) {
                $this->rowGateway->save();
            } else {
                $this->rowGateway->update();
            }
            // Else, save multiple rows
        } else {
            $this->tableGateway->insert($columns);

            $rows = $this->tableGateway->getRows();

            $this->setRows($rows, $rowsAs);
            if (isset($rows[0])) {
                $this->setColumns($rows[0]);
            }
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
        // Delete the record
        if (null === $columns) {
            $this->rowGateway->delete();
        // Delete multiple rows
        } else {
            $db  = Db::getDb($this->getFullTable());
            $sql = $db->createSql();

            $parsedColumns = Parser\Column::parse($columns, $sql->getPlaceholder());
            $this->tableGateway->delete($parsedColumns['where'], $parsedColumns['params']);
        }
        $this->setRows();
        $this->setColumns();
    }

    /**
     * Set the table from a class name
     *
     * @param  string $class
     * @return mixed
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
     * Add a 1:1 relationship
     *
     * @param  string $class
     * @param  mixed  $foreignKey
     * @return mixed
     */
    public function hasOne($class, $foreignKey = null)
    {
        if (null === $foreignKey) {
            $class = get_class($this);
            if (strpos($class, '\\') !== false) {
                $class = substr($class, (strrpos($class, '\\') + 1));
            } else if (strpos($class, '_') !== false) {
                $class = substr($class, (strrpos($class, '_') + 1));
            }
            $foreignKey = strtolower($class) . '_id';
        }
        $this->oneToOne[$class] = $foreignKey;
        return $this->getOne($class);
    }

    /**
     * Add a 1:many relationship
     *
     * @param  string $class
     * @param  mixed  $foreignKey
     * @return mixed
     */
    public function hasMany($class, $foreignKey = null)
    {
        if (null === $foreignKey) {
            $class = get_class($this);
            if (strpos($class, '\\') !== false) {
                $class = substr($class, (strrpos($class, '\\') + 1));
            } else if (strpos($class, '_') !== false) {
                $class = substr($class, (strrpos($class, '_') + 1));
            }
            $foreignKey = strtolower($class) . '_id';
        }
        $this->oneToMany[$class] = $foreignKey;
        return $this->getMany($class);
    }

    /**
     * Get a 1:1 relationship
     *
     * @param  string $class
     * @return mixed
     */
    public function getOne($class)
    {
        $result = null;

        if (!isset($this->hasOne[$class])) {
            $foreignKeys = (!is_array($this->oneToOne[$class])) ? [$this->oneToOne[$class]] : $this->oneToOne[$class];

            if (count($foreignKeys) == count($this->primaryKeys)) {
                $columns = [];
                foreach ($foreignKeys as $i => $key) {
                    $columns[$key] = $this->rowGateway[$this->primaryKeys[$i]];
                }
                $this->hasOne[$class] = $class::findBy($columns, ['limit' => 1], Record::AS_RECORD);
                $result = $this->hasOne[$class];
            }
        } else {
            $result = $this->hasOne[$class];
        }

        return $result;
    }

    /**
     * Get a 1:many relationship
     *
     * @param  string $class
     * @return mixed
     */
    public function getMany($class)
    {
        $result = null;

        if (!isset($this->hasMany[$class])) {
            $foreignKeys = (!is_array($this->oneToMany[$class])) ? [$this->oneToMany[$class]] : $this->oneToMany[$class];

            if (count($foreignKeys) == count($this->primaryKeys)) {
                $columns = [];
                foreach ($foreignKeys as $i => $key) {
                    $columns[$key] = $this->rowGateway[$this->primaryKeys[$i]];
                }
                $this->hasMany[$class] = new Record\Collection($class::findBy($columns, null, Record::AS_RECORD)->rows());
                $result = $this->hasMany[$class];
            }
        } else {
            $result = $this->hasMany[$class];
        }

        return $result;
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return Record
     */
    public function setColumns($columns = null)
    {
        if (null !== $columns) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->rowGateway->setColumns((array)$columns);
            } else if ($columns instanceof Record) {
                $this->rowGateway->setColumns($columns->toArray());
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
     * @param  string $rowsAs
     * @return Record
     */
    public function setRows(array $rows = null, $rowsAs = Record::AS_RECORD)
    {
        $this->rowGateway->setColumns();
        $this->tableGateway->setRows() ;

        if (null !== $rows) {
            $this->rowGateway->setColumns(((isset($rows[0])) ? (array)$rows[0] : []));
            foreach ($rows as $i => $row) {
                $rows[$i] = $this->processRow($row, $rowsAs);
            }
            $this->tableGateway->setRows($rows);
        }

        return $this;
    }

    /**
     * Process a table row
     *
     * @param  array  $row
     * @param  string $rowsAs
     * @return mixed
     */
    public function processRow(array $row, $rowsAs = Record::AS_RECORD)
    {
        switch ($rowsAs) {
            case self::AS_ARRAY:
                $row = (array)$row;
                break;
            case self::AS_OBJECT:
                $row = new \ArrayObject((array)$row, \ArrayObject::ARRAY_AS_PROPS);
                break;
            case self::AS_COLLECTION:
                $row = new Record\Collection($row);
                break;
            default:
                $r = new static();
                $r->setColumns((array)$row);
                $row = $r;
        }

        return $row;
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->rowGateway[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $result = null;

        if (isset($this->rowGateway[$name])) {
            $result = $this->rowGateway[$name];
        } else if (method_exists($this, $name)) {
            $result = $this->{$name}();
        }

        return $result;
    }

    /**
     * Magic method to return the isset value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->rowGateway[$name]);
    }

    /**
     * Magic method to unset $this->rowGateway[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->rowGateway[$name])) {
            unset($this->rowGateway[$name]);
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