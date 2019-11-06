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

    }

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @return static|Collection
     */
    public static function findById($id)
    {

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
     * @return Record\Collection
     */
    public static function findBy(array $columns = null, array $options = null)
    {

    }

    /**
     * Find by or create static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @return Record\Collection|Record
     */
    public static function findByOrCreate(array $columns = null, array $options = null)
    {

    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @return Record\Collection
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
     * @return Record\Collection
     */
    public static function execute($sql, array $params)
    {

    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @return Record\Collection
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