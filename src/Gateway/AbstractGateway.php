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
namespace Pop\Db\Gateway;

use Pop\Db\Sql;

/**
 * Db abstract gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractGateway implements GatewayInterface
{

    /**
     * Table
     * @var string
     */
    protected $table = null;

    /**
     * Sql object
     * @var Sql
     */
    protected $sql = null;

    /**
     * 1:1 relationships
     * @var array
     */
    protected $oneToOne = [];
    /**
     * 1:Many relationships
     * @var array
     */
    protected $oneToMany = [];

    /**
     * Constructor
     *
     * Instantiate the AbstractGateway object.
     *
     * @param  Sql    $sql
     * @param  string $table
     */
    public function __construct(Sql $sql, $table)
    {
        $this->table = $table;
        $this->sql   = $sql;
    }

    /**
     * Get the SQL object
     *
     * @return Sql
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the SQL object (alias method)
     *
     * @return Sql
     */
    public function sql()
    {
        return $this->sql;
    }

    /**
     * Set 1:1 relationships
     *
     * @param  array $oneToOne
     * @return AbstractGateway
     */
    public function setOneToOne(array $oneToOne)
    {
        $this->oneToOne = $oneToOne;
        return $this;
    }

    /**
     * Set 1:many relationships
     *
     * @param  array $oneToMany
     * @return AbstractGateway
     */
    public function setOneToMany(array $oneToMany)
    {
        $this->oneToMany = $oneToMany;
        return $this;
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
     * Get table info
     *
     * @return array
     */
    public function getTableInfo()
    {
        $info      = [
            'tableName' => $this->table,
            'columns'   => []
        ];

        $sql       = null;
        $field     = 'column_name';
        $type      = 'data_type';
        $nullField = 'is_nullable';
        $keyField  = 'constraint_type';

        switch ($this->sql->getDbType()) {
            case Sql::PGSQL:
            case Sql::SQLSRV:
                $sql = 'SELECT * FROM information_schema.columns ' .
                    'LEFT JOIN information_schema.table_constraints ' .
                    'ON information_schema.columns.table_name = information_schema.table_constraints.table_name ' .
                    'WHERE table_name = \'' . $this->table . '\' ORDER BY information_schema.ordinal_position ASC';
                break;
            case Sql::SQLITE:
                $sql       = 'PRAGMA table_info(\'' . $this->table . '\')';
                $field     = 'name';
                $type      = 'type';
                $nullField = 'notnull';
                $keyField  = 'pk';
                break;
            case Sql::ORACLE:
                $sql       = 'SELECT ALL_TAB_COLUMNS.COLUMN_NAME AS COLUMN_NAME, ' .
                    'ALL_TAB_COLUMNS.DATA_TYPE AS DATA_TYPE, ALL_TAB_COLUMNS.NULLABLE AS NULLABLE, ' .
                    'ALL_CONSTRAINTS.CONSTRAINT_TYPE AS CONSTRAINT_TYPE FROM ALL_TAB_COLUMNS ' .
                    'LEFT JOIN ALL_CONSTRAINTS ON ALL_CONSTRAINTS,TABLE_NAME = ALL_TAB_COLUMNS.TABLE_NAME ' .
                    'WHERE ALL_TAB_COLUMNS.TABLE_NAME = \'' . $this->table . '\'';
                $field     = 'COLUMN_NAME';
                $type      = 'DATA_TYPE';
                $nullField = 'NULLABLE';
                $keyField  = 'CONSTRAINT_TYPE';
                break;
            default:
                $sql       = 'SHOW COLUMNS FROM `' . $this->table . '`';
                $field     = 'Field';
                $type      = 'Type';
                $nullField = 'Null';
                $keyField  = 'Key';
        }

        $this->sql->db()->query($sql);

        while (($row = $this->sql->db()->fetch()) != false) {
            switch ($this->sql->getDbType()) {
                case Sql::SQLITE:
                    $nullResult    = !($row[$nullField]);
                    $primaryResult = ($row[$keyField] == 1);
                    break;
                case Sql::MYSQL:
                    $nullResult    = (strtoupper($row[$nullField]) != 'NO');
                    $primaryResult = (strtoupper($row[$keyField]) == 'PRI');
                    break;
                case Sql::ORACLE:
                    $nullResult    = (strtoupper($row[$nullField]) != 'Y');
                    $primaryResult = (strtoupper($row[$keyField]) == 'P');
                    break;
                default:
                    $nullResult    = $row[$nullField];
                    $primaryResult = (strtoupper($row[$keyField]) == 'PRIMARY KEY');

            }

            $info['columns'][$row[$field]] = [
                'type'    => $row[$type],
                'primary' => $primaryResult,
                'null'    => $nullResult
            ];
        }

        return $info;
    }

}