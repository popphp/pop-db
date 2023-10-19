<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;

use Pop\Db\Record\Collection;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
class Record extends Record\AbstractRecord
{

    /**
     * Constructor
     *
     * Instantiate the database record object
     *
     * Optional parameters are an array of column values, db adapter, or a table name
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
                $db = $arg;
            } else if (is_string($arg)) {
                $table = $arg;
            }
        }

        if ($table !== null) {
            $this->setTable($table);
        } else {
            $this->setTableFromClassName($class);
        }

        if ($db !== null) {
            Db::setDb($db, $class, null, ($class === __CLASS__));
        }

        if (!Db::hasDb($class)) {
            throw new Exception('Error: A database connection has not been set.');
        } else if (!Db::hasClassToTable($class)) {
            Db::addClassToTable($class, $this->getFullTable());
        }

        $this->tableGateway = new Gateway\Table($this->getFullTable());
        $this->rowGateway   = new Gateway\Row($this->getFullTable(), $this->primaryKeys);

        if ($columns !== null) {
            $this->isNew = true;
            $this->setColumns($columns);
        }
    }

/*
 * Static methods
 */

    /**
     * Check for a DB adapter
     *
     * @return bool
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
     * @param  bool                 $isDefault
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
     * @param  mixed   $id
     * @param  array   $options
     * @param  bool $asArray
     * @return static|array
     */
    public static function findById($id, array $options = null, $asArray = false)
    {
        return (new static())->getById($id, $options, $asArray);
    }

    /**
     * Find one static method
     *
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return static|array
     */
    public static function findOne(array $columns = null, array $options = null, $asArray = false)
    {
        return (new static())->getOne($columns, $options, $asArray);
    }

    /**
     * Find one or create static method
     *
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return static|array
     */
    public static function findOneOrCreate(array $columns = null, array $options = null, $asArray = false)
    {
        $result = (new static())->getOne($columns, $options);

        if (empty($result->toArray())) {
            $newRecord = new static($columns);
            $newRecord->save();
            $result = $newRecord;
        }

        return ($asArray) ? $result->toArray() : $result;
    }

    /**
     * Find latest static method
     *
     * @param  string  $by
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return static|array
     */
    public static function findLatest($by = null, array $columns = null, array $options = null, $asArray = false)
    {
        $record = new static();

        if (($by === null) && (count($record->getPrimaryKeys()) == 1)) {
            $by = $record->getPrimaryKeys()[0];
        }

        if ($by !== null) {
            if ($options === null) {
                $options = ['order' => $by . ' DESC'];
            } else {
                $options['order'] = $by . ' DESC';
            }
        }

        return $record->getOne($columns, $options, $asArray);
    }

    /**
     * Find by static method
     *
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return Collection|array
     */
    public static function findBy(array $columns = null, array $options = null, $asArray = false)
    {
        return (new static())->getBy($columns, $options, $asArray);
    }

    /**
     * Find by or create static method
     *
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return static|Collection|array
     */
    public static function findByOrCreate(array $columns = null, array $options = null, $asArray = false)
    {
        $result = (new static())->getBy($columns, $options);

        if ($result->count() == 0) {
            $newRecord = new static($columns);
            $newRecord->save();
            $result = $newRecord;
        }

        return ($asArray) ? $result->toArray() : $result;
    }

    /**
     * Find in static method
     *
     * @param  string  $key
     * @param  array   $values
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return array
     */
    public static function findIn($key, array $values, array $columns = null, array $options = null, $asArray = false)
    {
        return (new static())->getIn($key, $values, $columns, $options, $asArray);
    }

    /**
     * Find all static method
     *
     * @param  array   $options
     * @param  bool $asArray
     * @return static|Collection
     */
    public static function findAll(array $options = null, $asArray = false)
    {
        return static::findBy(null, $options, $asArray);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed   $sql
     * @param  array   $params
     * @param  bool $asArray
     * @return mixed
     */
    public static function execute($sql, array $params = [], $asArray = false)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = Db::getDb($record->getFullTable());
        $db->prepare($sql);
        if (!empty($params)) {
            $db->bindParams($params);
        }
        $db->execute();

        $rows     = [];
        $isSelect = false;

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $isSelect = true;
            $rows     = $db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $record->processRow($row, $asArray);
            }
        }

        if ($isSelect) {
            $collection = new Record\Collection($rows);
            return ($asArray) ? $collection->toArray() : $collection;
        } else {
            return null;
        }
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed   $sql
     * @param  bool $asArray
     * @return mixed
     */
    public static function query($sql, $asArray = false)
    {
        $record = new static();

        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $db = Db::getDb($record->getFullTable());
        $db->query($sql);

        $rows     = [];
        $isSelect = false;

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $isSelect = true;
            while (($row = $db->fetch())) {
                $rows[] = $record->processRow($row, $asArray);
            }
        }

        if ($isSelect) {
            $collection = new Record\Collection($rows);
            return ($asArray) ? $collection->toArray() : $collection;
        } else {
            return null;
        }
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array $columns
     * @param  array $options
     * @return int
     */
    public static function getTotal(array $columns = null, array $options = null)
    {
        $record      = new static();
        $expressions = null;
        $params      = null;

        if ($columns !== null) {
            $db            = Db::getDb($record->getFullTable());
            $sql           = $db->createSql();
            ['expressions' => $expressions, 'params' => $params] =
                Sql\Parser\Expression::parseShorthand($columns, $sql->getPlaceholder());
        }

        $rows = $record->getTableGateway()->select(['total_count' => 'COUNT(1)'], $expressions, $params, $options);

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
     * With a 1:many relationship (eager-loading)
     *
     * @param  mixed $name
     * @param  array  $options
     * @return static
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

/*
 * Instance methods
 */

    /**
     * Get by ID method
     *
     * @param  mixed   $id
     * @param  array   $options
     * @param  bool $asArray
     * @return static|array
     */
    public function getById($id, array $options = null, $asArray = false)
    {
        $this->setColumns($this->getRowGateway()->find($id, [], $options));
        if ($this->hasWiths()) {
            $this->getWithRelationships(false);
        }
        return ($asArray) ? $this->toArray() : $this;
    }

    /**
     * Get one method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  bool $asArray
     * @return static|array
     */
    public function getOne(array $columns = null, array $options = null, $asArray = false)
    {
        if ($options === null) {
            $options = ['limit' => 1];
        } else {
            $options['limit'] = 1;
        }

        $expressions = null;
        $params      = null;
        $select      = $options['select'] ?? null;

        if ($columns !== null) {
            $db            = Db::getDb($this->getFullTable());
            $sql           = $db->createSql();
            ['expressions' => $expressions, 'params' => $params] =
                Sql\Parser\Expression::parseShorthand($columns, $sql->getPlaceholder());
        }

        $rows = $this->getTableGateway()->select($select, $expressions, $params, $options);

        if ($this->hasWiths() && !empty($rows)) {
            $this->getWithRelationships();
            $this->processWithRelationships($rows);
        }

        if (isset($rows[0])) {
            $this->setColumns($rows[0]);
        }

        return ($asArray) ? $this->toArray() : $this;
    }

    /**
     * Get by method
     *
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return Collection|array
     */
    public function getBy(array $columns = null, array $options = null, $asArray = false)
    {
        $expressions = null;
        $params      = null;
        $select      = $options['select'] ?? null;

        if ($columns !== null) {
            $db            = Db::getDb($this->getFullTable());
            $sql           = $db->createSql();
            ['expressions' => $expressions, 'params' => $params] =
                Sql\Parser\Expression::parseShorthand($columns, $sql->getPlaceholder());
        }

        $rows = $this->getTableGateway()->select($select, $expressions, $params, $options);

        foreach ($rows as $i => $row) {
            $rows[$i] = $this->processRow($row);
        }

        if ($this->hasWiths() && !empty($rows)) {
            $this->getWithRelationships();
            $this->processWithRelationships($rows);
        }

        $collection = new Record\Collection($rows);
        return ($asArray) ? $collection->toArray() : $collection;
    }

    /**
     * Get in method
     *
     * @param  string  $key
     * @param  array   $values
     * @param  array   $columns
     * @param  array   $options
     * @param  bool $asArray
     * @return array
     */
    public function getIn($key, array $values, array $columns = null, array $options = null, $asArray = false)
    {
        $columns = ($columns !== null) ? array_merge([$key => $values], $columns) : [$key => $values];
        $results = $this->getBy($columns, $options, $asArray);
        $rows    = [];

        foreach ($results as $row) {
            if (isset($row[$key])) {
                $rows[$row[$key]] = (($asArray) && ($row instanceof \Pop\Db\Record)) ? $row->toArray() : $row;
            }
        }

        return $rows;
    }

    /**
     * Get all method
     *
     * @param  array   $options
     * @param  bool $asArray
     * @return Collection
     */
    public function getAll(array $options = null, $asArray = false)
    {
        return $this->getBy(null, $options, $asArray);
    }

    /**
     * Has one relationship
     *
     * @param  string  $foreignTable
     * @param  string  $foreignKey
     * @param  array   $options
     * @param  bool $eager
     * @return Record|Record\Relationships\HasOne
     */
    public function hasOne($foreignTable, $foreignKey, array $options = null, $eager = false)
    {
        $relationship = new Record\Relationships\HasOne($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren)) {
            $relationship->setChildRelationships($this->withChildren);
        }
        return ($eager) ? $relationship : $relationship->getChild($options);
    }

    /**
     * Has one of relationship
     *
     * @param  string  $foreignTable
     * @param  string  $foreignKey
     * @param  array   $options
     * @param  bool $eager
     * @return Record|Record\Relationships\HasOneOf
     */
    public function hasOneOf($foreignTable, $foreignKey, array $options = null, $eager = false)
    {
        $relationship = new Record\Relationships\HasOneOf($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren)) {
            $relationship->setChildRelationships($this->withChildren);
        }
        return ($eager) ? $relationship : $relationship->getChild();
    }

    /**
     * Has many relationship
     *
     * @param  string  $foreignTable
     * @param  string  $foreignKey
     * @param  array   $options
     * @param  bool $eager
     * @return Collection|Record\Relationships\HasMany
     */
    public function hasMany($foreignTable, $foreignKey, array $options = null, $eager = false)
    {
        $relationship = new Record\Relationships\HasMany($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren)) {
            $relationship->setChildRelationships($this->withChildren);
        }
        return ($eager) ? $relationship : $relationship->getChildren($options);
    }

    /**
     * Belongs to relationship
     *
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @param  array   $options
     * @param  bool $eager
     * @return Record|Record\Relationships\BelongsTo
     */
    public function belongsTo($foreignTable, $foreignKey, array $options = null, $eager = false)
    {
        $relationship = new Record\Relationships\BelongsTo($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren)) {
            $relationship->setChildRelationships($this->withChildren);
        }
        return ($eager) ? $relationship : $relationship->getParent($options);
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
     * @return bool
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
     * Reset row's dirty columns
     *
     * @return void
     */
    public function resetDirty()
    {
        $this->rowGateway->resetDirty();
    }

    /**
     * Save or update the record
     *
     * @param  array $columns
     * @return void
     */
    public function save(array $columns = null)
    {
        // Save or update the record
        if ($columns === null) {
            if ($this->isNew) {
                $this->rowGateway->save();
                $this->isNew = false;
            } else {
                $this->rowGateway->update();
                $record = $this->getById($this->rowGateway->getPrimaryValues());
                if (isset($record[0])) {
                    $this->setColumns($record[0]);
                }
            }
        // Else, save multiple rows
        } else {
            if (isset($columns[0])) {
                $this->tableGateway->insertRows($columns);
            } else {
                $this->tableGateway->insert($columns);
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
        if ($columns === null) {
            $this->rowGateway->delete();
        // Delete multiple rows
        } else {
            $expressions = null;
            $params      = [];

            if ($columns !== null) {
                $db            = Db::getDb($this->getFullTable());
                $sql           = $db->createSql();
                ['expressions' => $expressions, 'params' => $params] =
                    Sql\Parser\Expression::parseShorthand($columns, $sql->getPlaceholder());
            }

            $this->tableGateway->delete($expressions, $params);
        }

        $this->setRows();
        $this->setColumns();
    }

    /**
     * Call static method for 'findWhere'
     *
     *      $users = Users::findWhereUsername('testuser');
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $record = null;

        if (substr($name, 0, 9) == 'findWhere') {
            $column = Sql\Parser\Table::parse(substr($name, 9));
            $arg1   = $arguments[0] ?? null;
            $arg2   = $arguments[1] ?? null;

            if ($arg1 !== null) {
                $record = static::findBy([$column => $arg1], $arg2);
            }
        }

        return $record;
    }

}