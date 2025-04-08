<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;

use Pop\Db\Record\Collection;
use Pop\Utils\CallableObject;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 * @method     static findWhereEquals($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNotEquals($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereGreaterThan($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereGreaterThanOrEqual($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereLessThan($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereLessThanOrEqual($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereLike($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNotLike($column, $value, array $options = null, bool|array $toArray = false)
 * @method     static findWhereIn($column, $values, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNotIn($column, $values, array $options = null, bool|array $toArray = false)
 * @method     static findWhereBetween($column, $values, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNotBetween($column, $values, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNull($column, array $options = null, bool|array $toArray = false)
 * @method     static findWhereNotNull($column, array $options = null, bool|array $toArray = false)
 */
class Record extends Record\AbstractRecord
{

    /**
     * Constructor
     *
     * Instantiate the database record object
     *
     * Optional parameters are an array of column values, db adapter, or a table name

     * @throws Exception|Record\Exception
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
        } else if ($this->table !== null) {
            $this->setTable($this->table);
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
    public static function hasDb(): bool
    {
        return Db::hasDb(get_called_class());
    }

    /**
     * Set DB adapter
     *
     * @param  Adapter\AbstractAdapter $db
     * @param  ?string                 $prefix
     * @param  bool                    $isDefault
     * @return void
     */
    public static function setDb(Adapter\AbstractAdapter $db, ?string $prefix = null, bool $isDefault = false): void
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
    public static function setDefaultDb(Adapter\AbstractAdapter $db): void
    {
        Db::setDb($db, null, null, true);
    }

    /**
     * Get DB adapter
     *
     * @return Adapter\AbstractAdapter
     */
    public static function getDb(): Adapter\AbstractAdapter
    {
        return Db::getDb(get_called_class());
    }

    /**
     * Get DB adapter (alias)
     *
     * @return Adapter\AbstractAdapter
     */
    public static function db(): Adapter\AbstractAdapter
    {
        return Db::db(get_called_class());
    }

    /**
     * Get SQL builder
     *
     * @return Sql
     */
    public static function getSql(): Sql
    {
        return Db::db(get_called_class())->createSql();
    }

    /**
     * Get SQL builder (alias)
     *
     * @return Sql
     */
    public static function sql(): Sql
    {
        return Db::db(get_called_class())->createSql();
    }

    /**
     * Get table name
     *
     * @param  bool $quotes
     * @return string
     */
    public static function table(bool $quotes = false): string
    {
        $table = (new static())->getFullTable();
        $sql   = static::sql();
        if ($quotes) {
            $table = $sql->quoteId($table);
        }
        return $table;
    }

    /**
     * Start transaction with the DB adapter. When called on a descendent class, construct
     * a new object and use it for transaction management.
     *
     * @param mixed ...$constructorArgs Arguments passed to descendent class constructor
     * @return static|null
     * @throws Exception|Record\Exception
     */
    public static function start(mixed ...$constructorArgs): static|null
    {
        $class = get_called_class();

        if ($class !== Record::class) {
            $record = new static(...$constructorArgs);
            $record->startTransaction();
            return $record;
        } else {
            if (Db::hasDb($class)) {
                Db::db($class)->beginTransaction();
            }
            return null;
        }
    }

    /**
     * Commit transaction with the DB adapter
     *
     * @throws Exception
     * @return void
     */
    public static function commit(): void
    {
        $class = get_called_class();
        if (Db::hasDb($class)) {
            Db::db($class)->commit();
        }
    }

    /**
     * Rollback transaction with the DB adapter
     *
     * @param  \Exception|null $exception
     * @throws Exception
     * @return \Exception|null
     */
    public static function rollback(\Exception $exception = null): \Exception|null
    {
        $class = get_called_class();

        if (Db::hasDb($class)) {
            if (Db::db($class)->getTransactionDepth() == 1) {
                Db::db($class)->rollback();
            } else {
                if ($exception == null) {
                    $exception = new Exception('Error: A rollback has been executed from within a nested transaction.');
                }
                return $exception;
            }
        }

        return null;
    }

    /**
     * Execute complete transaction with the DB adapter
     *
     * @param  mixed $callable
     * @param  mixed $params
     * @throws \Exception
     * @return void
     */
    public static function transaction(mixed $callable, mixed $params = null): void
    {
        if (!($callable instanceof CallableObject)) {
            $callable = new CallableObject($callable, $params);
        }

        try {
            static::start();
            $callable->call();
            static::commit();
        } catch (\Exception $e) {
            $result = static::rollback($e);
            throw (!empty($result)) ? $result : $e;
        }
    }

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @param  ?array $options
     * @param  bool   $toArray
     * @return static|array
     */
    public static function findById(mixed $id, ?array $options = null, bool $toArray = false): array|static
    {
        return (new static())->getById($id, $options, $toArray);
    }

    /**
     * Find one static method
     *
     * @param  ?array $columns
     * @param  ?array $options
     * @param  bool   $toArray
     * @return static|array
     */
    public static function findOne(?array $columns = null, ?array $options = null, bool $toArray = false): array|static
    {
        return (new static())->getOne($columns, $options, $toArray);
    }

    /**
     * Find one or create static method
     *
     * @param  ?array $columns
     * @param  ?array $options
     * @param  bool   $toArray
     * @return static|array
     */
    public static function findOneOrCreate(?array $columns = null, ?array $options = null, bool $toArray = false): array|static
    {
        $result = (new static())->getOne($columns, $options);

        if (empty($result->toArray())) {
            $newRecord = new static($columns);
            $newRecord->save();
            $result = $newRecord;
        }

        return ($toArray) ? $result->toArray() : $result;
    }

    /**
     * Find latest static method
     *
     * @param  ?string $by
     * @param  ?array  $columns
     * @param  ?array  $options
     * @param  bool    $toArray
     * @return static|array
     */
    public static function findLatest(?string $by = null, ?array $columns = null, ?array $options = null, bool $toArray = false): array|static
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

        return $record->getOne($columns, $options, $toArray);
    }

    /**
     * Find by static method
     *
     * @param  ?array     $columns
     * @param  ?array     $options
     * @param  bool|array $toArray
     * @return Collection|array
     */
    public static function findBy(?array $columns = null, ?array $options = null, bool|array $toArray = false): Collection|array
    {
        return (new static())->getBy($columns, $options, $toArray);
    }

    /**
     * Find by or create static method
     *
     * @param  ?array     $columns
     * @param  ?array     $options
     * @param  bool|array $toArray
     * @return static|Collection|array
     */
    public static function findByOrCreate(?array $columns = null, ?array $options = null, bool|array $toArray = false): Collection|array|static
    {
        $result = (new static())->getBy($columns, $options);

        if ($result->count() == 0) {
            $newRecord = new static($columns);
            $newRecord->save();
            $result = $newRecord;
            return ($toArray !== false) ? $result->toArray() : $result;
        } else {
            return ($toArray !== false) ? $result->toArray($toArray) : $result;
        }
    }

    /**
     * Find in static method
     *
     * @param  string  $key
     * @param  array   $values
     * @param  ?array  $columns
     * @param  ?array  $options
     * @param  bool    $toArray
     * @return array
     */
    public static function findIn(string $key, array $values, ?array $columns = null, ?array $options = null, bool|array $toArray = false): array
    {
        return (new static())->getIn($key, $values, $columns, $options, $toArray);
    }

    /**
     * Find all static method
     *
     * @param  ?array $options
     * @param  bool   $toArray
     * @return Collection|array|static
     */
    public static function findAll(?array $options = null, bool|array $toArray = false): Collection|array|static
    {
        return static::findBy(null, $options, $toArray);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed $sql
     * @param  array $params
     * @param  bool  $toArray
     * @return Collection|array|int
     */
    public static function execute(mixed $sql, array $params = [], bool|array $toArray = false): Collection|array|int
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
                $rows[$i] = $record->processRow($row, $toArray);
            }
        }

        if ($isSelect) {
            $collection = new Record\Collection($rows);
            return ($toArray !== false) ? $collection->toArray($toArray) : $collection;
        } else {
            return self::db()->getNumberOfAffectedRows();
        }
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed $sql
     * @param  bool  $toArray
     * @return Collection|array|int
     */
    public static function query(mixed $sql, bool|array $toArray = false): Collection|array|int
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
                $rows[] = $record->processRow($row, $toArray);
            }
        }

        if ($isSelect) {
            $collection = new Record\Collection($rows);
            return ($toArray !== false) ? $collection->toArray($toArray) : $collection;
        } else {
            return self::db()->getNumberOfAffectedRows();
        }
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  ?array $columns
     * @param  ?array $options
     * @return int
     */
    public static function getTotal(?array $columns = null, ?array $options = null): int
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
    public static function getTableInfo(): array
    {
        return (new static())->getTableGateway()->getTableInfo();
    }

    /**
     * With a 1:many relationship (eager-loading)
     *
     * @param  mixed  $name
     * @param  ?array $options
     * @return static
     */
    public static function with(mixed $name, ?array $options = null): static
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
     * @param  mixed  $id
     * @param  ?array $options
     * @param  bool   $toArray
     * @return static|array
     */
    public function getById(mixed $id, ?array $options = null, bool $toArray = false): Record|array|static
    {
        $this->setColumns($this->getRowGateway()->find($id, [], $options));
        if ($this->hasWiths()) {
            $this->getWithRelationships(false);
        }
        return ($toArray) ? $this->toArray() : $this;
    }

    /**
     * Get one method
     *
     * @param  ?array $columns
     * @param  ?array $options
     * @param  bool   $toArray
     * @return static|array
     */
    public function getOne(?array $columns = null, ?array $options = null, bool $toArray = false): Record|array|static
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

        return ($toArray) ? $this->toArray() : $this;
    }

    /**
     * Get by method
     *
     * @param  ?array $columns
     * @param  ?array $options
     * @param  bool   $toArray
     * @return Collection|array
     */
    public function getBy(?array $columns = null, ?array $options = null, bool|array $toArray = false): Collection|array
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
        return ($toArray !== false) ? $collection->toArray($toArray) : $collection;
    }

    /**
     * Get in method
     *
     * @param  string $key
     * @param  array  $values
     * @param  ?array $columns
     * @param  ?array $options
     * @param  bool   $toArray
     * @return array
     */
    public function getIn(string $key, array $values, array $columns = null, array $options = null, bool|array $toArray = false): array
    {
        $columns = ($columns !== null) ? array_merge([$key => $values], $columns) : [$key => $values];
        $results = $this->getBy($columns, $options, $toArray);
        $rows    = [];

        foreach ($results as $row) {
            if (isset($row[$key])) {
                $rows[$row[$key]] = (($toArray !== false) && ($row instanceof Record)) ? $row->toArray() : $row;
            }
        }

        return $rows;
    }

    /**
     * Get all method
     *
     * @param  ?array $options
     * @param  bool   $toArray
     * @return Collection|array
     */
    public function getAll(?array $options = null, bool|array $toArray = false): Collection|array
    {
        return $this->getBy(null, $options, $toArray);
    }

    /**
     * Has one relationship
     *
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @param  ?array $options
     * @param  bool   $eager
     * @return Record|Record\Relationships\HasOne
     */
    public function hasOne(string $foreignTable, string $foreignKey, ?array $options = null, bool $eager = false): Record|Record\Relationships\HasOne
    {
        $relationship = new Record\Relationships\HasOne($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren) && !empty($this->withChildren[$this->currentWithIndex])) {
            $relationship->setChildRelationships($this->withChildren[$this->currentWithIndex]);
        }
        return ($eager) ? $relationship : $relationship->getChild($options);
    }

    /**
     * Has one of relationship
     *
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @param  ?array $options
     * @param  bool   $eager
     * @return Record|Record\Relationships\HasOneOf
     */
    public function hasOneOf(string $foreignTable, string $foreignKey, ?array $options = null, bool $eager = false): Record|Record\Relationships\HasOneOf
    {
        $relationship = new Record\Relationships\HasOneOf($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren) && !empty($this->withChildren[$this->currentWithIndex])) {
            $relationship->setChildRelationships($this->withChildren[$this->currentWithIndex]);
        }
        return ($eager) ? $relationship : $relationship->getChild();
    }

    /**
     * Has many relationship
     *
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @param  ?array $options
     * @param  bool   $eager
     * @return mixed
     */
    public function hasMany(string $foreignTable, string $foreignKey, ?array $options = null, bool $eager = false): mixed
    {
        if (($this->latest) || ($this->oldest)) {
            if ($options !== null) {
                $options['order'] = $this->relationshipSortBy . ' ' . (($this->latest) ? 'DESC' : 'ASC');
                $options['limit'] = 1;
            } else {
                $options = [
                    'order' => $this->relationshipSortBy . ' ' . (($this->latest) ? 'DESC' : 'ASC'),
                    'limit' => 1
                ];
            }
        }

        $relationship = new Record\Relationships\HasMany($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren) && !empty($this->withChildren[$this->currentWithIndex])) {
            $relationship->setChildRelationships($this->withChildren[$this->currentWithIndex]);
        }

        if ($eager) {
            return $relationship;
        } else {
            $children = $relationship->getChildren($options);
            return ((($this->latest) || ($this->oldest)) && (count($children) == 1)) ? $children[0] : $children;
        }
    }

    /**
     * Belongs to relationship
     *
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @param  ?array $options
     * @param  bool   $eager
     * @return Record|Record\Relationships\BelongsTo
     */
    public function belongsTo(string $foreignTable, string $foreignKey, ?array $options = null, bool $eager = false): Record|Record\Relationships\BelongsTo
    {
        $relationship = new Record\Relationships\BelongsTo($this, $foreignTable, $foreignKey, $options);
        if (!empty($this->withChildren) && !empty($this->withChildren[$this->currentWithIndex])) {
            $relationship->setChildRelationships($this->withChildren[$this->currentWithIndex]);
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
    public function increment(string $column, int $amount = 1): void
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
    public function decrement(string $column, int $amount = 1): void
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
    public function replicate(array $replace = []): static
    {
        $fields = $this->toArray();

        foreach ($this->primaryKeys as $key) {
            if (isset($fields[$key])) {
                unset($fields[$key]);
            }
        }

        if (!empty($replace)) {
            foreach ($replace as $key => $value) {
                if (array_key_exists($key, $fields)) {
                    $fields[$key] = $value;
                }
            }
        }

        $newRecord = new static($fields);
        $newRecord->save();

        return $newRecord;
    }

    /**
     * Copy the record (alias to replicate)
     *
     * @param  array $replace
     * @return static
     */
    public function copy(array $replace = []): static
    {
        return $this->replicate($replace);
    }

    /**
     * Check if row is dirty
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->rowGateway->isDirty();
    }

    /**
     * Get row's dirty columns
     *
     * @return array
     */
    public function getDirty(): array
    {
        return $this->rowGateway->getDirty();
    }

    /**
     * Reset row's dirty columns
     *
     * @return void
     */
    public function resetDirty(): void
    {
        $this->rowGateway->resetDirty();
    }

    /**
     * Save or update the record
     *
     * @param  ?array $columns
     * @param  bool   $commit
     * @throws \Exception
     * @return void
     */
    public function save(array $columns = null, bool $commit = true): void
    {
        try {
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
            if (($this->isTransaction()) && ($commit)) {
                $this->commitTransaction();
            }
        } catch (\Exception $e) {
            if (($this->isTransaction()) && ($commit)) {
                $this->rollbackTransaction();
            }
            throw $e;
        }
    }

    /**
     * Delete the record
     *
     * @param  ?array $columns
     * @param  bool   $commit
     * @return void
     */
    public function delete(array $columns = null, bool $commit = true): void
    {
        try {
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

            if (($this->isTransaction()) && ($commit)) {
                $this->commitTransaction();
            }
        } catch (\Exception $e) {
            if (($this->isTransaction()) && ($commit)) {
                $this->rollbackTransaction();
            }
            throw $e;
        }

    }

    /**
     * Call static method for 'findWhere'
     *
     *     $users = Users::findWhereUsername($value);
     *
     *     $users = Users::findWhereEquals($column, $value);
     *     $users = Users::findWhereNotEquals($column, $value);
     *     $users = Users::findWhereGreaterThan($column, $value);
     *     $users = Users::findWhereGreaterThanOrEqual($column, $value);
     *     $users = Users::findWhereLessThan($column, $value);
     *     $users = Users::findWhereLessThanOrEqual($column, $value);
     *
     *     $users = Users::findWhereLike($column, $value);
     *     $users = Users::findWhereNotLike($column, $value);
     *
     *     $users = Users::findWhereIn($column, $values);
     *     $users = Users::findWhereNotIn($column, $values);
     *
     *     $users = Users::findWhereBetween($column, $values);
     *     $users = Users::findWhereNotBetween($column, $values);
     *
     *     $users = Users::findWhereNull($column);
     *     $users = Users::findWhereNotNull($column);
     *
     * @param  string $name
     * @param  array  $arguments
     * @return Collection|array|null
     */
    public static function __callStatic(string $name, array $arguments): Collection|array|null
    {
        $columns    = null;
        $options    = null;
        $toArray    = false;
        $conditions = [
            'Equals', 'NotEquals', 'GreaterThan', 'GreaterThanOrEqual', 'LessThan', 'LessThanOrEqual',
            'Like', 'NotLike', 'In', 'NotIn', 'Between', 'NotBetween', 'Null', 'NotNull'
        ];

        if (str_starts_with($name, 'findWhere')) {
            if (in_array(substr($name, 9), $conditions)) {
                $condition = substr($name, 9);
                $column    = $arguments[0];

                if (str_contains($condition, 'Null')) {
                    $value     = null;
                    $options   = $arguments[1] ?? null;
                    $toArray   = $arguments[2] ?? false;
                } else {
                    $value     = $arguments[1];
                    $options   = $arguments[2] ?? null;
                    $toArray   = $arguments[3] ?? false;
                }

                switch ($condition) {
                    case 'Equals':
                    case 'In':
                    case 'Between':
                    case 'Null':
                        $columns = [$column => $value];
                        break;
                    case 'NotEquals':
                        $columns = [$column . '!=' => $value];
                        break;
                    case 'GreaterThan':
                        $columns = [$column . '>' => $value];
                        break;
                    case 'GreaterThanOrEqual':
                        $columns = [$column . '>=' => $value];
                        break;
                    case 'LessThan':
                        $columns = [$column . '<' => $value];
                        break;
                    case 'LessThanOrEqual':
                        $columns = [$column . '<=' => $value];
                        break;
                    case 'Like':
                        if (str_starts_with($value, '%')) {
                            $column = '%' . $column;
                            $value  = substr($value, 1);
                        }
                        if (str_ends_with($value, '%')) {
                            $column .= '%';
                            $value   = substr($value, 0, -1);
                        }
                        $columns = [$column => $value];
                        break;
                    case 'NotLike':
                        if (str_starts_with($value, '%')) {
                            $column = '-%' . $column;
                            $value  = substr($value, 1);
                        }
                        if (str_ends_with($value, '%')) {
                            $column .= '%-';
                            $value   = substr($value, 0, -1);
                        }
                        $columns = [$column => $value];
                        break;
                    case 'NotIn':
                    case 'NotBetween':
                    case 'NotNull':
                        $columns = [$column . '-' => $value];
                        break;
                }
            } else {
                $column  = Sql\Parser\Table::parse(substr($name, 9));
                $value   = $arguments[0] ?? null;
                $options = $arguments[1] ?? null;
                $toArray = $arguments[2] ?? false;

                if ($value !== null) {
                    $columns = [$column => $value];
                }
            }
        }

        return ($columns !== null) ? static::findBy($columns, $options, $toArray) : null;
    }

}
