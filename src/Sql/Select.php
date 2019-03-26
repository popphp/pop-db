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
 * Select class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Select extends AbstractClause
{

    /**
     * Allowed functions
     * @var array
     */
    protected static $allowedFunctions = [
        'AVG', 'COUNT', 'FIRST', 'LAST', 'MAX', 'MIN', 'SUM'
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
     * WHERE predicate object
     * @var Where
     */
    protected $where = null;

    /**
     * HAVING predicate object
     * @var Having
     */
    protected $having = null;

    /**
     * GROUP BY value
     * @var string
     */
    protected $groupBy = null;

    /**
     * ORDER BY value
     * @var string
     */
    protected $orderBy = null;

    /**
     * LIMIT value
     * @var mixed
     */
    protected $limit = null;

    /**
     * OFFSET value
     * @var int
     */
    protected $offset = null;

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
     * @param  mixed  $table
     * @return Select
     */
    public function from($table)
    {
        $this->setTable($table);
        return $this;
    }

    /**
     * Set table AS alias name
     *
     * @param  mixed  $table
     * @return Select
     */
    public function asAlias($table)
    {
        $this->setAlias($table);
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
        $this->joins[] = new Join($this, $foreignTable, $columns, $join);
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
     * @param  mixed $where
     * @return Select
     */
    public function where($where = null)
    {
        if (null !== $where) {
            if ($where instanceof Where) {
                $this->where = $where;
            } else {
                if (null === $this->where) {
                    $this->where = (new Where($this))->add($where);
                } else {
                    $this->where->add($where);
                }
            }
        }
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        return $this;
    }

    /**
     * Access the WHERE clause with AND
     *
     * @param  mixed $where
     * @return Select
     */
    public function andWhere($where = null)
    {
        if (empty($this->where)) {
            $this->where = new Where($this);
        }
        if ($this->where->hasPredicates()) {
            $this->where->getLastPredicateSet()->setCombine('AND');
        }
        $this->where($where);
        return $this;
    }

    /**
     * Access the WHERE clause with OR
     *
     * @param  mixed $where
     * @return Select
     */
    public function orWhere($where = null)
    {
        if (empty($this->where)) {
            $this->where = new Where($this);
        }
        if ($this->where->hasPredicates()) {
            $this->where->getLastPredicateSet()->setCombine('OR');
        }
        $this->where($where);
        return $this;
    }

    /**
     * Access the HAVING clause
     *
     * @param  mixed $having
     * @return Select
     */
    public function having($having = null)
    {
        if (null !== $having) {
            if ($having instanceof Having) {
                $this->having = $having;
            } else {
                if (null === $this->having) {
                    $this->having = (new Having($this))->add($having);
                } else {
                    $this->having->add($having);
                }
            }
        }
        if (null === $this->having) {
            $this->having = new Having($this);
        }

        return $this;
    }

    /**
     * Access the HAVING clause with AND
     *
     * @param  mixed $having
     * @return Select
     */
    public function andHaving($having = null)
    {
        if ($this->having->hasPredicates()) {
            $this->having->getLastPredicateSet()->setCombine('AND');
        }
        $this->having($having);
        return $this;
    }

    /**
     * Access the HAVING clause with OR
     *
     * @param  mixed $having
     * @return Select
     */
    public function orHaving($having = null)
    {
        if ($this->having->hasPredicates()) {
            $this->having->getLastPredicateSet()->setCombine('OR');
        }
        $this->having($having);
        return $this;
    }

    /**
     * Set the GROUP BY value
     *
     * @param mixed $by
     * @return Select
     */
    public function groupBy($by)
    {
        $byColumns = null;

        if (is_array($by)) {
            $quotedAry = [];
            foreach ($by as $value) {
                $quotedAry[] = $this->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else if (strpos($by, ',') !== false) {
            $ary = explode(',' , $by);
            $quotedAry = [];
            foreach ($ary as $value) {
                $quotedAry[] = $this->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else {
            $byColumns = $this->quoteId(trim($by));
        }

        $this->groupBy = $byColumns;
        return $this;
    }

    /**
     * Set the ORDER BY value
     *
     * @param  mixed  $by
     * @param  string $order
     * @return Select
     */
    public function orderBy($by, $order = 'ASC')
    {
        $byColumns = null;

        if (is_array($by)) {
            $quotedAry = [];
            foreach ($by as $value) {
                $quotedAry[] = $this->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else if (strpos($by, ',') !== false) {
            $ary = explode(',' , $by);
            $quotedAry = [];
            foreach ($ary as $value) {
                $quotedAry[] = $this->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else {
            $byColumns = $this->quoteId(trim($by));
        }

        $this->orderBy .= ((null !== $this->orderBy) ? ', ' : '') . $byColumns;
        $order = strtoupper($order);

        if (strpos($order, 'RAND') !== false) {
            $this->orderBy .= ($this->isSqlite()) ? ' RANDOM()' : ' RAND()';
        } else if (($order == 'ASC') || ($order == 'DESC')) {
            $this->orderBy .= ' ' . $order;
        }

        return $this;
    }

    /**
     * Set the LIMIT value
     *
     * @param mixed $limit
     * @return Select
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET value
     *
     * @param  int $offset
     * @return Select
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * Render the SELECT statement
     *
     * @throws Exception
     * @return string
     */
    public function render()
    {
        // Start building the SELECT statement
        $sql = 'SELECT ' . (($this->distinct) ? 'DISTINCT ' : null);

        if (count($this->values) > 0) {
            $cols = [];
            foreach ($this->values as $as => $col) {
                // If column is a SQL function, don't quote it
                $c = ((strpos($col, '(') !== false) && (in_array(substr($col, 0, strpos($col, '(')), self::$allowedFunctions))) ?
                    $col : $this->quoteId($col);
                if (!is_numeric($as)) {
                    $cols[] = $c . ' AS ' . $this->quoteId($as);
                } else {
                    $cols[] = $c;
                }
            }
            $sql .= implode(', ', $cols) . ' ';
        } else {
            $sql .= '* ';
        }

        $sql .= 'FROM ';

        // Account for LIMIT and OFFSET clauses if the database is SQLSRV
        if (($this->isSqlsrv()) && ((null !== $this->limit) || (null !== $this->offset))) {
            if (null === $this->orderBy) {
                throw new Exception(
                    'Error: You must set an order by clause to execute a limit clause on the SQL server database.'
                );
            }
            $sql .= $this->buildSqlSrvLimitAndOffset();
        // Else, if there is a nested SELECT statement.
        } else if (($this->table instanceof \Pop\Db\Sql) && ($this->table->hasSelect())) {
            $sql .= (string)$this->table->select();
        // Else, if there is a nested SELECT statement.
        } else if ($this->table instanceof \Pop\Db\Sql\Select) {
            $sql .= (string)$this->table;
        // Else, just select from the table
        } else {
            $sql .= $this->quoteId($this->table);
        }

        // Build any JOIN clauses
        if (count($this->joins) > 0) {
            foreach ($this->joins as $join) {
                $sql .= ' ' . $join;
            }
        }

        // Build any WHERE clauses
        if (null !== $this->where) {
            $sql .= ' WHERE ' . $this->where;
        }

        // Build any HAVING clause
        if (null !== $this->having) {
            $sql .= ' HAVING ' . $this->having;
        }

        // Build any GROUP BY clause
        if (null !== $this->groupBy) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        // Build any ORDER BY clause
        if (null !== $this->orderBy) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }

        // Build any LIMIT clause for all other database types.
        if (!$this->isSqlsrv()) {
            if (null !== $this->limit) {
                if ((strpos($this->limit, ',') !== false) && ($this->isPgsql())) {
                    $ary = explode(',', $this->limit);
                    $this->offset = (int)trim($ary[0]);
                    $this->limit = (int)trim($ary[1]);
                }
                $sql .= ' LIMIT ' . $this->limit;
            }
        }

        // Build any OFFSET clause for all other database types.
        if (!$this->isSqlsrv()) {
            if (null !== $this->offset) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        if (null !== $this->alias) {
            $sql = '(' . $sql . ') AS ' . $this->quoteId($this->alias);
        }

        return $sql;
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

    /**
     * Magic method to access $where and $having properties
     *
     * @param  string $name
     * @throws Exception
     * @return mixed
     */
    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'where':
                if (null === $this->where) {
                    $this->where = new Where($this);
                }
                return $this->where;
                break;
            case 'having':
                if (null === $this->having) {
                    $this->having = new Having($this);
                }
                return $this->having;
                break;
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

    /**
     * Method to get the limit and offset
     *
     * @return array
     */
    protected function getLimitAndOffset()
    {
        $result = [
            'limit'  => null,
            'offset' => null
        ];

        // Calculate the limit and/or offset
        if (null !== $this->offset) {
            $result['offset'] = (int)$this->offset + 1;
            $result['limit']  = (null !== $this->limit) ? (int)$this->limit + (int)$this->offset : 0;
        } else if (strpos($this->limit, ',') !== false) {
            $ary  = explode(',', $this->limit);
            $result['offset'] = (int)trim($ary[0]) + 1;
            $result['limit']  = (int)trim($ary[1]) + (int)trim($ary[0]);
        } else {
            $result['limit']  = (int)$this->limit;
        }

        return $result;
    }

    /**
     * Method to build SQL Server limit and offset sub-clause
     *
     * @return string
     */
    protected function buildSqlSrvLimitAndOffset()
    {
        $sql    = null;
        $result = $this->getLimitAndOffset();
        if (null !== $result['offset']) {
            if (null === $this->where) {
                $this->where = new Where($this);
            }

            $sql .= '(SELECT *, ROW_NUMBER() OVER (ORDER BY ' . $this->orderBy . ') AS RowNumber FROM ' .
                $this->quoteId($this->table) . ') AS OrderedTable';
            if ($result['limit'] > 0) {
                $this->where->between('OrderedTable.RowNumber', $result['offset'], $result['limit']);
            } else {
                $this->where->greaterThanOrEqualTo('OrderedTable.RowNumber', $result['offset']);
            }
        } else {
            $sql  = str_replace('SELECT', 'SELECT TOP ' . $result['limit'], $sql);
            $sql .= $this->quoteId($this->table);
        }

        return $sql;
    }

}