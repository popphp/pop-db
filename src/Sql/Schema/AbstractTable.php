<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\AbstractSql;
use Pop\Db\Gateway;

/**
 * Abstract schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractTable extends AbstractSql
{

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Table info
     * @var array
     */
    protected $info = [];

    /**
     * Constructor
     *
     * Instantiate the table object
     *
     * @param  string          $table
     * @param  AbstractAdapter $db
     */
    public function __construct($table, AbstractAdapter $db)
    {
        $this->table = $table;
        parent::__construct($db);

        $tableGateway = new Gateway\Table($table);
        $this->info   = $tableGateway->getTableInfo($db);
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
     * Get the table info
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Render the table schema to an array of statements
     *
     * @return array
     */
    public function renderToStatements()
    {
        $statements    = explode(';' . PHP_EOL, $this->render());
        $sqlStatements = [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $sqlStatements[] = $statement;
            }
        }

        return $sqlStatements;
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