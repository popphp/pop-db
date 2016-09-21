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
namespace Pop\Db\Sql;

/**
 * Select class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Select extends AbstractSql
{

    /**
     * Allowed functions
     * @var array
     */
    protected static $allowedFunctions = [
        'AVG', 'COUNT', 'FIRST', 'LAST', 'MAX', 'MIN', 'SUM'
    ];

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
     * Distinct keyword
     * @var boolean
     */
    protected $distinct = false;

    /**
     * Joins
     * @var array
     */
    protected $joins = [];

    /**
     * Select distinct
     *
     * @param  boolean $distinct
     * @return Select
     */
    public function distinct($distinct = true)
    {
        $this->distinct = (bool)$distinct;
        return $this;
    }

    /**
     * Set from table
     *
     * @param  string $table
     * @param  string $alias
     * @return Select
     */
    public function from($table, $alias = null)
    {
        $this->setTable($table);
        if (null !== $alias) {
            $this->setAlias($alias);
        }
        return $this;
    }

    /**
     * Add a JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array  $columns
     * @param  string $join
     * @return Select
     */
    public function join($foreignTable, array $columns, $join = 'JOIN')
    {
        // If it's a sub-select
        if ($foreignTable instanceof Select) {
            $alias = ($foreignTable->hasAlias()) ? $foreignTable->getAlias() : $foreignTable->getTable();
            $table = '(' . $foreignTable . ') AS ' . $this->quoteId($alias);
        } else {
            $table = $this->quoteId($foreignTable);
        }

        $this->joins[] = [
            'foreignTable' => $table,
            'columns'      => $columns,
            'typeOfJoin'   => (in_array(strtoupper($join), self::$allowedJoins)) ? strtoupper($join) : 'JOIN'
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function leftJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'LEFT JOIN');
    }

    /**
     * Add a RIGHT JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function rightJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'RIGHT JOIN');
    }

    /**
     * Add a FULL JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function fullJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'FULL JOIN');
    }

    /**
     * Add a OUTER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function outerJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'OUTER JOIN');
    }

    /**
     * Add a LEFT OUTER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function leftOuterJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'LEFT OUTER JOIN');
    }

    /**
     * Add a RIGHT OUTER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function rightOuterJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'RIGHT OUTER JOIN');
    }

    /**
     * Add a FULL OUTER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function fullOuterJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'FULL OUTER JOIN');
    }

    /**
     * Add a INNER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function innerJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'INNER JOIN');
    }

    /**
     * Add a LEFT INNER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function leftInnerJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'LEFT INNER JOIN');
    }

    /**
     * Add a RIGHT INNER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function rightInnerJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'RIGHT INNER JOIN');
    }

    /**
     * Add a FULL INNER JOIN clause
     *
     * @param  mixed $foreignTable
     * @param  array $columns
     * @return Select
     */
    public function fullInnerJoin($foreignTable, array $columns)
    {
        return $this->join($foreignTable, $columns, 'FULL INNER JOIN');
    }

    /**
     * Access the WHERE clause
     *
     */
    public function where()
    {

    }

    /**
     * Access the GROUP BY clause
     *
     */
    public function groupBy()
    {

    }

    /**
     * Access the HAVING clause
     *
     */
    public function having()
    {

    }

    /**
     * Render the SELECT statement
     *
     * @return string
     */
    public function render()
    {
        return '';
    }

    /**
     * Render the SELECT statement
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}