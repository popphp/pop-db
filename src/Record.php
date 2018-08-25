<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;
use Pop\Db\Record\Collection;
use Pop\Db\Sql\Where;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.3.0
 */
class Record extends Record\AbstractRecord
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

        if (null !== $table) {
            $this->setTable($table);
        }

        // Set the table name from the class name
        if (null === $this->table) {
            $this->setTableFromClassName($class);
        }

        if (null !== $db) {
            Db::setDb($db, $class, null, ($class === __CLASS__));
        }

        if (!Db::hasDb($class)) {
            throw new Exception('Error: A database connection has not been set.');
        } else if (!Db::hasClassToTable($class)) {
            Db::addClassToTable($class, $this->getFullTable());
        }

        $this->tableGateway = new Gateway\Table($this->getFullTable());
        $this->rowGateway   = new Gateway\Row($this->getFullTable(), $this->primaryKeys);
        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
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
     * Get SQL builder
     *
     * @return Sql
     */
    public static function getSql()
    {
        return Db::db(get_called_class())->createSql();
    }

    /**
     * Get SQL builder (alias)
     *
     * @return Sql
     */
    public static function sql()
    {
        return Db::db(get_called_class())->createSql();
    }

    /**
     * Get table name
     *
     * @return string
     */
    public static function table()
    {
        return (new static())->getFullTable();
    }

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @return static|Collection
     */
    public static function findById($id)
    {
        $record = new static();
        return $record->getById($id);
    }

    /**
     * Find one static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static
     */
    public static function findOne(array $columns = null, array $options = null)
    {
        $record = new static();
        return $record->getOneBy($columns, $options);
    }

    /**
     * Find one or create static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static
     */
    public static function findOneOrCreate(array $columns = null, array $options = null)
    {
        $record = new static();
        $result = $record->getOneBy($columns, $options);

        if (empty($result->toArray())) {
            $newRecord = new static($columns);
            $newRecord->save();
            return $newRecord;
        } else {
            return $result;
        }
    }

    /**
     * Find latest static method
     *
     * @param  string $by
     * @param  array  $columns
     * @param  array  $options
     * @return static
     */
    public static function findLatest($by = null, array $columns = null, array $options = null)
    {
        $record = new static();

        if ((null === $by) && (count($record->getPrimaryKeys()) == 1)) {
            $by = $record->getPrimaryKeys()[0];
        }
        if (null === $options) {
            $options = ['order' => $by . ' DESC'];
        } else {
            $options['order'] = $by . ' DESC';
        }
        return $record->getOneBy($columns, $options);
    }

    /**
     * Find by static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultAs
     * @return Record\Collection
     */
    public static function findBy(array $columns = null, array $options = null, $resultAs = Record::AS_RECORD)
    {
        $record = new static();
        return $record->getBy($columns, $options, $resultAs);
    }

    /**
     * Find by or create static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultAs
     * @return Record\Collection
     */
    public static function findByOrCreate(array $columns = null, array $options = null, $resultAs = Record::AS_RECORD)
    {
        $record = new static();
        $result = $record->getBy($columns, $options, $resultAs);

        if ($result->count() == 0) {
            $newRecord = new static($columns);
            $newRecord->save();
            return $newRecord;
        } else {
            return $result;
        }
    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @param  string $resultAs
     * @return Record\Collection
     */
    public static function findAll(array $options = null, $resultAs = Record::AS_RECORD)
    {
        return static::findBy(null, $options, $resultAs);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  array  $params
     * @param  string $resultAs
     * @return Record\Collection
     */
    public static function execute($sql, array $params, $resultAs = Record::AS_RECORD)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = Db::getDb($record->getFullTable());

        $db->prepare($sql)
           ->bindParams($params)
           ->execute();

        $rows = [];
        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $record->processRow($row, $resultAs);
            }
        }

        return new Record\Collection($rows);
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultAs
     * @return Record\Collection
     */
    public static function query($sql, $resultAs = Record::AS_RECORD)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = Db::getDb($record->getFullTable());

        $db->query($sql);

        $rows = [];
        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            while (($row = $db->fetch())) {
                $rows[] = $record->processRow($row, $resultAs);
            }
        }

        return new Record\Collection($rows);
    }

    /**
     * With a 1:many relationship (eager-loading)
     *
     * @param  mixed $name
     * @param  array  $options
     * @return mixed
     */
    public static function with($name, array $options = null)
    {
        $record = new static();

        if (is_array($name)) {
            foreach ($name as $key => $value) {
                if (is_numeric($key) && is_string($value)) {
                    $record->addWith($value);
                } else if (!is_numeric($key) && is_array($value)) {
                    $record->addWith($key, $value);
                }
            }
        } else {
            $record->addWith($name, $options);
        }
        return $record;
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @return int
     */
    public static function getTotal(array $columns = null)
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

        $rows = $record->getTableGateway()->select(['total_count' => 'COUNT(1)'], $where, $params);

        return (isset($rows[0]) && isset($rows[0]['total_count'])) ? (int)$rows[0]['total_count'] : 0;
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
     * Get by ID method
     *
     * @param  mixed  $id
     * @return static|Collection
     */
    public function getById($id)
    {
        if (is_array($id) && (count($this->primaryKeys) == 1)) {
            $record      = new static();
            $db          = Db::getDb($record->getFullTable());
            $sql         = $db->createSql();
            $placeholder = $sql->getPlaceholder();
            $params      = [];

            $sql->select()->from($record->getFullTable());

            foreach ($id as $i) {
                $sql->select()->orWhere($this->primaryKeys[0] . ' IN ' . $placeholder);
                $params[] = $i;
            }

            $db->prepare((string)$sql)
               ->bindParams($params)
               ->execute();

            return new Collection($db->fetchAll());
        } else {
            $this->setColumns($this->getRowGateway()->find($id));

            if (null !== $this->with) {
                $this->getWith();
            }

            return $this;
        }
    }

    /**
     * Get one method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static
     */
    public function getOneBy(array $columns = null, array $options = null)
    {
        if (null === $options) {
            $options = ['limit' => 1];
        } else {
            $options['limit'] = 1;
        }

        $params = null;
        $where  = null;
        $select = (isset($options['select'])) ? $options['select'] : null;

        if (null !== $columns) {
            $db  = Db::getDb($this->getFullTable());
            $sql = $db->createSql();

            $parsedColumns = Parser\Column::parse($columns, $sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows  = $this->getTableGateway()->select($select, $where, $params, $options);

        if (isset($rows[0])) {
            $this->setColumns($rows[0]);
        }

        if (null !== $this->with) {
            $this->getWith();
        }

        return $this;
    }

    /**
     * Get by method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultAs
     * @return Record\Collection
     */
    public function getBy(array $columns = null, array $options = null, $resultAs = Record::AS_RECORD)
    {
        $params = null;
        $where  = null;
        $select = (isset($options['select'])) ? $options['select'] : null;

        if (null !== $columns) {
            $db  = Db::getDb($this->getFullTable());
            $sql = $db->createSql();

            $parsedColumns = Parser\Column::parse($columns, $sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows = $this->getTableGateway()->select($select, $where, $params, $options);

        foreach ($rows as $i => $row) {
            $rows[$i] = $this->processRow($row, $resultAs);
            if ((null !== $this->with) && ($rows[$i] instanceof Record)) {
                $rows[$i]->getWith();
            }
        }

        return new Record\Collection($rows);
    }

    /**
     * Get all method
     *
     * @param  array  $options
     * @param  string $resultAs
     * @return Record\Collection
     */
    public function getAll(array $options = null, $resultAs = Record::AS_RECORD)
    {
        return $this->getBy(null, $options, $resultAs);
    }

    /**
     * Increment the record column and save
     *
     * @param  string $column
     * @param  int    $amount
     * @return void
     */
    public function increment($column, $amount = 1)
    {
        $this->{$column} += (int)$amount;
        $this->save();
    }

    /**
     * Decrement the record column and save
     *
     * @param  string $column
     * @param  int    $amount
     * @return void
     */
    public function decrement($column, $amount = 1)
    {
        $this->{$column} -= (int)$amount;
        $this->save();
    }

    /**
     * Replicate the record
     *
     * @param  array $replace
     * @return static
     */
    public function replicate(array $replace = [])
    {
        $fields = $this->toArray();

        foreach ($this->primaryKeys as $key) {
            if (isset($fields[$key])) {
                unset($fields[$key]);
            }
        }

        if (!empty($replace)) {
            foreach ($replace as $key => $value) {
                if (isset($fields[$key])) {
                    $fields[$key] = $value;
                }
            }
        }

        $newRecord = new static($fields);
        $newRecord->save();

        return $newRecord;
    }

    /**
     * Check if row is dirty
     *
     * @return boolean
     */
    public function isDirty()
    {
        return $this->rowGateway->isDirty();
    }

    /**
     * Get row's dirty columns
     *
     * @return array
     */
    public function getDirty()
    {
        return $this->rowGateway->getDirty();
    }

    /**
     * Save the record
     *
     * @param  array  $columns
     * @return void
     */
    public function save(array $columns = null)
    {
        // Save or update the record
        if (null === $columns) {
            if ($this->isNew) {
                $this->rowGateway->save();
                $this->isNew = false;
            } else {
                $this->rowGateway->update();
            }
        // Else, save multiple rows
        } else {
            $this->tableGateway->insert($columns);
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
     * @param  string  $class
     * @param  mixed   $foreignKey
     * @param  array   $options
     * @param  boolean $eager
     * @return mixed
     */
    public function hasMany($class, $foreignKey = null, array $options = null, $eager = false)
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

        if ($eager) {
            return $this->getManyEager($class, $options);
        } else {
            return $this->getMany($class, $options);
        }
    }

    /**
     * Add a 1:1 belongs to relationship
     *
     * @param  string $class
     * @param  mixed  $foreignKey
     * @return mixed
     */
    public function belongsTo($class, $foreignKey = null)
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
        $this->belongsTo[$class] = $foreignKey;

        return $this->getBelong($class);
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
                $collection = $class::findBy($columns, ['limit' => 1], Record::AS_RECORD);
                if (isset($collection[0])) {
                    $this->hasOne[$class] = $collection[0];
                    $result = $this->hasOne[$class];
                }
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
     * @param  array  $options
     * @return mixed
     */
    public function getMany($class, array $options = null)
    {
        $result = null;
        $id     = (count($this->primaryKeys) == 1) ? $this->rowGateway[$this->primaryKeys[0]] : null;

        if ((null !== $id) && isset($this->relationships[$id])) {
            $many = [];
            foreach ($this->relationships[$id] as $relationship) {
                if ($relationship instanceof $class) {
                    $many[] = $relationship;
                }
            }
            $this->hasMany[$class] = new Record\Collection($many);
            $result = $this->hasMany[$class];
        } else if (!isset($this->hasMany[$class])) {
            $foreignKeys = (!is_array($this->oneToMany[$class])) ? [$this->oneToMany[$class]] : $this->oneToMany[$class];

            if (count($foreignKeys) == count($this->primaryKeys)) {
                $columns = [];
                foreach ($foreignKeys as $i => $key) {
                    $columns[$key] = $this->rowGateway[$this->primaryKeys[$i]];
                }
                if ((null !== $options) && isset($options['columns'])) {
                    $columns = array_merge($columns, $options['columns']);
                    unset($options['columns']);
                }
                $this->hasMany[$class] = new Record\Collection($class::findBy($columns, $options));
                $result = $this->hasMany[$class];
            }
        } else {
            $result = $this->hasMany[$class];
        }

        return $result;
    }

    /**
     * Get a 1:many eager relationship
     *
     * @param  string $class
     * @param  array  $options
     * @return array
     */
    public function getManyEager($class, array $options = null)
    {
        $record = new $class();
        $db     = Db::getDb($record->getFullTable());
        $sql    = $db->createSql();
        $rows   = $this->tableGateway->rows();
        $ids    = [];
        $values = [];

        if (count($rows) == 0) {
            $rows = [$this->rowGateway->getColumns()];
        }

        foreach ($rows as $row) {
            foreach ($this->primaryKeys as $key) {
                $ids[]    = $row[$key];
                $values[] = $sql->getPlaceholder();
            }
        }

        $sql->select()->from($record->getFullTable());
        $foreignKeys = (!is_array($this->oneToMany[$class])) ? [$this->oneToMany[$class]] : $this->oneToMany[$class];
        if (count($foreignKeys) == count($this->primaryKeys)) {
            foreach ($foreignKeys as $i => $key) {
                $sql->select()->where->in($key, $values);
            }
        }

        if (isset($options['limit'])) {
            $sql->select()->limit((int)$options['limit']);
        }

        if (isset($options['offset'])) {
            $sql->select()->offset((int)$options['offset']);
        }

        if ((null !== $options) && isset($options['order'])) {
            $order = Parser\Order::parse($options['order']);
            $sql->select()->orderBy($order['by'], $db->escape($order['order']));
        }

        $db->prepare($sql)
           ->bindParams($ids)
           ->execute();

        $rows       = $db->fetchAll();
        $parentRows = $this->processRows($this->tableGateway->getRows(), Record::AS_RECORD);

        if (count($parentRows) == 0) {
            $parentRows = $this->processRows([$this->rowGateway->getColumns()], Record::AS_RECORD);
        }

        if ((count($this->primaryKeys) == 1) && (count($foreignKeys) == 1)) {
            $foreignKey = $foreignKeys[0];
            $primaryKey = $this->primaryKeys[0];

            foreach ($parentRows as $parent) {
                foreach ($rows as $row) {
                    if ($row[$foreignKey] == $parent[$primaryKey]) {
                        $r = new $class();
                        $r->setColumns($row);
                        if (!isset($options['limit']) ||
                            (isset($options['limit']) && (count($parent->getRelationships($parent[$this->primaryKeys[$i]])) < (int)$options['limit']))) {
                            $parent->addRelationship($parent[$this->primaryKeys[$i]], $r);
                        }
                    } else {
                        $parent->addRelationship($parent[$this->primaryKeys[$i]]);
                    }
                }
            }
        }

        return $parentRows;
    }

    /**
     * Get a 1:1 belongs to relationship
     *
     * @param  string $class
     * @return mixed
     */
    public function getBelong($class)
    {
        $result = null;

        if (!isset($this->doesBelong[$class])) {
            $foreignKeys = (!is_array($this->belongsTo[$class])) ? [$this->belongsTo[$class]] : $this->belongsTo[$class];

            $id = [];
            foreach ($foreignKeys as $i => $key) {
                if (isset($this->rowGateway[$key])) {
                    $id[] = $this->rowGateway[$key];
                }
            }
            if (count($id) > 0) {
                if (count($id) == 1) {
                    $id = $id[0];
                }
                $this->doesBelong[$class] = $class::findById($id);
                $result = $this->doesBelong[$class];
            }
        } else {
            $result = $this->doesBelong[$class];
        }

        return $result;
    }

    /**
     * Call static method
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 9) == 'findWhere') {
            $column = Parser\Table::parse(substr($name, 9));
            if (count($arguments) == 1) {
                return static::findBy([$column => $arguments[0]]);
            }
        }
    }

}