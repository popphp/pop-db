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
class Record extends AbstractRecord
{

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @param  string $resultsAs
     * @return mixed
     */
    public static function findById($id, $resultsAs = Result::AS_OBJECT)
    {
        return (new static())->getResult()->findById($id, $resultsAs);
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

}