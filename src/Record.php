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

use Pop\Db\Record\Collection;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
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

        if (null !== $table) {
            $this->setTable($table);
        } else {
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

/*
 * Static methods
 */

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @return static
     */
    public static function findById($id)
    {
        return (new static())->getById($id);
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

    }

    /**
     * Find by static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static|Collection
     */
    public static function findBy(array $columns = null, array $options = null)
    {

    }

    /**
     * Find by or create static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static|Collection
     */
    public static function findByOrCreate(array $columns = null, array $options = null)
    {

    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @return static|Collection
     */
    public static function findAll(array $options = null)
    {
        return static::findBy(null, $options);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  array  $params
     * @return static|Collection
     */
    public static function execute($sql, array $params)
    {

    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @return static|Collection
     */
    public static function query($sql)
    {

    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @return int
     */
    public static function getTotal(array $columns = null)
    {

    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @return array
     */
    public static function getTableInfo()
    {

    }

/*
 * Instance methods
 */

    /**
     * Get by ID method
     *
     * @param  mixed  $id
     * @return static
     */
    public function getById($id)
    {
        $this->setColumns($this->getRowGateway()->find($id));
        return $this;
    }

    /**
     * Get one method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return static
     */
    public function getOne(array $columns = null, array $options = null)
    {
        if (null === $options) {
            $options = ['limit' => 1];
        } else {
            $options['limit'] = 1;
        }

        $expressions = null;
        $params      = null;
        $select      = $options['select'] ?? null;

        if (null !== $columns) {
            $db            = Db::getDb($this->getFullTable());
            $sql           = $db->createSql();
            ['expressions' => $expressions, 'params' => $params] =
                Sql\Parser\Expression::parseShorthand($columns, $sql->getPlaceholder());
        }

        $rows = $this->getTableGateway()->select($select, $expressions, $params, $options);

        if (isset($rows[0])) {
            $this->setColumns($rows[0]);
        }

        return $this;
    }

    /**
     * Get by method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return Collection
     */
    public function getBy(array $columns = null, array $options = null)
    {

    }

    /**
     * Get all method
     *
     * @param  array  $options
     * @return Collection
     */
    public function getAll(array $options = null)
    {
        return $this->getBy(null, $options);
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

    }

    /**
     * Replicate the record
     *
     * @param  array $replace
     * @return static
     */
    public function replicate(array $replace = [])
    {

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
     * @param  array  $columns
     * @return void
     */
    public function save(array $columns = null)
    {

    }

    /**
     * Delete the record
     *
     * @param  array $columns
     * @return void
     */
    public function delete(array $columns = null)
    {

    }

    /**
     * Call static method for 'findWhere'
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed|void
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 9) == 'findWhere') {
            $column = Sql\Parser\Table::parse(substr($name, 9));
            $arg1   = $arguments[0] ?? null;
            $arg2   = $arguments[1] ?? null;

            if (null !== $arg1) {
                return static::findBy([$column => $arg1], $arg2);
            }
        }
    }

}