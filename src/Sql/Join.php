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
namespace Pop\Db\Sql;

/**
 * Join class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Join
{

    /**
     * Allowed JOIN keywords
     * @var array
     */
    protected static $allowedJoins = [
        'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL JOIN',
        'OUTER JOIN', 'LEFT OUTER JOIN', 'RIGHT OUTER JOIN', 'FULL OUTER JOIN',
        'INNER JOIN', 'LEFT INNER JOIN', 'RIGHT INNER JOIN', 'FULL INNER JOIN'
    ];

    /**
     * SQL object
     * @var AbstractSql
     */
    protected $sql = null;

    /**
     * Foreign table
     * @var string
     */
    protected $foreignTable = null;

    /**
     * Columns
     * @var array
     */
    protected $columns = [];

    /**
     * Join type
     * @var string
     */
    protected $join = 'JOIN';

    /**
     * Constructor
     *
     * Instantiate the JOIN object
     *
     * @param  AbstractSql $sql
     * @param  mixed       $foreignTable
     * @param  array       $columns
     * @param  string      $join
     */
    public function __construct($sql, $foreignTable, array $columns, $join = 'JOIN')
    {
        $this->sql = $sql;

        // If it's a sub-select
        if (($foreignTable instanceof Select) || ($foreignTable instanceof \Pop\Db\Sql)) {
            $this->foreignTable = (string)$foreignTable;
        } else if (is_array($foreignTable)) {
            foreach ($foreignTable as $alias => $table) {
                $this->foreignTable = $this->sql->quoteId($table) . ' AS ' . $this->sql->quoteId($alias);
                break;
            }
        } else {
            $this->foreignTable = $this->sql->quoteId($foreignTable);
        }

        $this->columns = $columns;
        $this->join    = (in_array(strtoupper($join), self::$allowedJoins)) ? strtoupper($join) : 'JOIN';
    }

    /**
     * Get foreign table
     *
     * @return string
     */
    public function getForeignTable()
    {
        return $this->foreignTable;
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get JOIN type
     *
     * @return string
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     * Render JOIN
     *
     * @return string
     */
    public function render()
    {
        $columns = [];
        foreach ($this->columns as $column1 => $column2) {
            if (is_array($column2)) {
                foreach ($column2 as $c) {
                    $columns[] = ((strpos($column1, '.') !== false) ? $this->sql->quoteId($column1) : $column1) . ' = ' .
                        ((strpos($c, '.') !== false) ? $this->sql->quoteId($c) : $c);
                }
            } else {
                $columns[] = $this->sql->quoteId($column1) . ' = ' . $this->sql->quoteId($column2);
            }
        }

        return $this->join . ' ' . $this->foreignTable . ' ON (' . implode(' AND ', $columns) . ')';
    }

    /**
     * Return JOIN as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}