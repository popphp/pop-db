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
namespace Pop\Db\Sql\Schema;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\AbstractSql;

/**
 * Abstract schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractTable extends AbstractSql
{

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Constructor
     *
     * Instantiate the table object
     *
     * @param  string          $table
     * @param  AbstractAdapter $db
     */
    public function __construct($table, $db)
    {
        $this->table = $table;
        parent::__construct($db);
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    abstract public function render();

    /**
     * Render the table schema to string
     *
     * @return string
     */
    abstract public function __toString();

}