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
use Pop\Db\Sql;
use Pop\Db\Gateway;
use Pop\Db\Parser;

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
     * Data set result constants
     * @var string
     */
    const AS_ARRAY  = 'AS_ARRAY';
    const AS_OBJECT = 'AS_OBJECT';
    const AS_RESULT = 'AS_RESULT';

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
     * Constructor
     *
     * Instantiate the database abstract record object
     *
     * @param  AbstractAdapter $db
     * @param  string          $table
     */
    public function __construct(AbstractAdapter $db, $table)
    {
        $this->db    = $db;
        $this->sql   = $db->createSql();
        $this->table = $table;

        $this->tableGateway = new Gateway\Table($this->sql, $table);
    }

    /**
     * Get the DB adapter
     *
     * @return AbstractAdapter
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Get the SQL object
     *
     * @return Sql
     */
    public function sql()
    {
        return $this->sql;
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
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @return int
     */
    public function getTotal(array $columns = null)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows = $this->tableGateway->select(['total_count' => 'COUNT(1)'], $where, $params);


        return (isset($rows[0]) && isset($rows[0]['total_count'])) ? (int)$rows[0]['total_count'] : 0;
    }

    /**
     * Get table info and return as an array
     *
     * @return array
     */
    public function getTableInfo()
    {
        return $this->tableGateway->getTableInfo();
    }

}