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

use Pop\Db\Gateway;

/**
 * Abstract record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractRecord
{

    /**
     * Constants to set individual row data type
     * @var string
     */
    const AS_ARRAY      = 'AS_ARRAY';
    const AS_OBJECT     = 'AS_OBJECT';
    const AS_COLLECTION = 'AS_COLLECTION';
    const AS_RECORD     = 'AS_RECORD';

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
     * Is new record flag
     * @var boolean
     */
    protected $isNew = false;

    /**
     * 1:1 associations
     * @var array
     */
    protected $oneToOne = [];

    /**
     * 1:1 association objects
     * @var array
     */
    protected $hasOne = [];

    /**
     * 1:Many associations
     * @var array
     */
    protected $oneToMany = [];

    /**
     * 1:many association objects
     * @var array
     */
    protected $hasMany = [];

    /**
     * Set the table
     *
     * @param  string $table
     * @return AbstractRecord
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return AbstractRecord
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
     * @return AbstractRecord
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
     * Get the row gateway
     *
     * @return Gateway\Row
     */
    public function getRowGateway()
    {
        return $this->rowGateway;
    }

    /**
     * Get the table gateway
     *
     * @return Gateway\Table
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->rowGateway->getColumns();
    }

    /**
     * Get column values as array object
     *
     * @return \ArrayObject
     */
    public function toArrayObject()
    {
        return new \ArrayObject($this->rowGateway->getColumns(), \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get column values as collection
     *
     * @return Collection
     */
    public function toCollection()
    {
        return new Collection($this->rowGateway->getColumns());
    }

    /**
     * Get the rows
     *
     * @return Collection
     */
    public function getRows()
    {
        return new Collection($this->tableGateway->getRows());
    }

    /**
     * Get the rows (alias method)
     *
     * @return Collection
     */
    public function rows()
    {
        return $this->getRows();
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function count()
    {
        return $this->tableGateway->getNumberOfRows();
    }

    /**
     * Determine if the result has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return ($this->tableGateway->getNumberOfRows() > 0);
    }

}