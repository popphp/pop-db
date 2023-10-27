<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Select extends AbstractPredicateClause
{

    /**
     * Distinct keyword
     * @var bool
     */
    protected bool $distinct = false;

    /**
     * Joins
     * @var array
     */
    protected array $joins = [];

    /**
     * HAVING predicate object
     * @var ?Having
     */
    protected ?Having $having = null;

    /**
     * GROUP BY value
     * @var ?string
     */
    protected ?string $groupBy = null;

    /**
     * ORDER BY value
     * @var ?string
     */
    protected ?string $orderBy = null;

    /**
     * LIMIT value
     * @var mixed
     */
    protected mixed $limit = null;

    /**
     * OFFSET value
     * @var ?int
     */
    protected ?int $offset = null;

    /**
     * Select distinct
     *
     * @param  bool $distinct
     * @return Select
     */
    public function distinct(bool $distinct = true): Select
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
    public function from(mixed $table): Select
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
    public function asAlias(mixed $table): Select
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
    public function join(mixed $foreignTable, array $columns, string $join = 'JOIN'): Select
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
    public function leftJoin(mixed $foreignTable, array $columns): Select
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
    public function rightJoin(mixed $foreignTable, array $columns): Select
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
    public function fullJoin(mixed $foreignTable, array $columns): Select
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
    public function outerJoin(mixed $foreignTable, array $columns): Select
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
    public function leftOuterJoin(mixed $foreignTable, array $columns): Select
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
    public function rightOuterJoin(mixed $foreignTable, array $columns): Select
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
    public function fullOuterJoin(mixed $foreignTable, array $columns): Select
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
    public function innerJoin(mixed $foreignTable, array $columns): Select
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
    public function leftInnerJoin(mixed $foreignTable, array $columns): Select
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
    public function rightInnerJoin(mixed $foreignTable, array $columns): Select
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
    public function fullInnerJoin(mixed $foreignTable, array $columns): Select
    {
        return $this->join($foreignTable, $columns, 'FULL INNER JOIN');
    }

    /**
     * Access the HAVING clause
     *
     * @param  mixed $having
     * @return Select
     */
    public function having(mixed $having = null): Select
    {
        if ($this->having === null) {
            $this->having = new Having($this);
        }

        if ($having !== null) {
            if (is_string($having)) {
                if ((stripos($having, ' AND ') !== false) || (stripos($having, ' OR ') !== false)) {
                    $expressions = array_map('trim', preg_split(
                        '/(AND|OR)/', $having, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                    ));
                    foreach ($expressions as $i => $expression) {
                        if (isset($expressions[$i - 1]) && (strtoupper($expressions[$i - 1]) == 'AND')) {
                            $this->having->and($expression);
                        } else if (isset($expressions[$i - 1]) && (strtoupper($expressions[$i - 1]) == 'OR')) {
                            $this->having->or($expression);
                        } else if (($expression != 'AND') && ($expression != 'OR')) {
                            $this->having->add($expression);
                        }
                    }
                } else {
                    $this->having->add($having);
                }
            } else if (is_array($having)) {
                $this->having->addExpressions($having);
            }
        }

        return $this;
    }

    /**
     * Access the HAVING clause with AND
     *
     * @param  mixed $having
     * @return Select
     */
    public function andHaving(mixed $having = null): Select
    {
        if ($this->having === null) {
            $this->having = new Having($this);
        }

        if ($having !== null) {
            if (is_string($having)) {
                $this->having->and($having);
            } else if (is_array($having)) {
                foreach ($having as $h) {
                    $this->having->and($h);
                }
            }
        }

        return $this;
    }

    /**
     * Access the HAVING clause with OR
     *
     * @param  mixed $having
     * @return Select
     */
    public function orHaving(mixed $having = null): Select
    {
        if ($this->having === null) {
            $this->having = new Having($this);
        }

        if ($having !== null) {
            if (is_string($having)) {
                $this->having->or($having);
            } else if (is_array($having)) {
                foreach ($having as $h) {
                    $this->having->or($h);
                }
            }
        }

        return $this;
    }

    /**
     * Set the GROUP BY value
     *
     * @param mixed $by
     * @return Select
     */
    public function groupBy(mixed $by): Select
    {
        if (is_array($by)) {
            $this->groupBy = implode(', ', array_map([$this, 'quoteId'], array_map('trim', $by)));
        } else if (str_contains($by, ',')) {
            $this->groupBy = implode(', ', array_map([$this, 'quoteId'], array_map('trim', explode(',' , $by))));
        } else {
            $this->groupBy = $this->quoteId(trim($by));
        }

        return $this;
    }

    /**
     * Set the ORDER BY value
     *
     * @param  mixed  $by
     * @param  string $order
     * @return Select
     */
    public function orderBy(mixed $by, string $order = 'ASC'): Select
    {
        $byColumns = null;
        $order     = strtoupper($order);

        if (is_array($by)) {
            $byColumns = implode(', ', array_map([$this, 'quoteId'], array_map('trim', $by)));
        } else if (str_contains($by, ',')) {
            $byColumns = implode(', ', array_map([$this, 'quoteId'], array_map('trim', explode(',' , $by))));
        } else {
            $byColumns = $this->quoteId(trim($by));
        }

        $this->orderBy .= (($this->orderBy !== null) ? ', ' : '') . $byColumns;

        if (str_contains($order, 'RAND')) {
            $this->orderBy .= ($this->isSqlite()) ? ' RANDOM()' : ' RAND()';
        } else if (($order == 'ASC') || ($order == 'DESC')) {
            $this->orderBy .= ' ' . $order;
        }

        return $this;
    }

    /**
     * Set the LIMIT value
     *
     * @param  int $limit
     * @return Select
     */
    public function limit(int $limit): Select
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
    public function offset(int $offset): Select
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Render the SELECT statement
     *
     * @throws Exception
     * @return string
     */
    public function render(): string
    {
        // Start building the SELECT statement
        $sql = 'SELECT ' . (($this->distinct) ? 'DISTINCT ' : null);

        if (count($this->values) > 0) {
            $cols = [];
            foreach ($this->values as $as => $col) {
                // If column is a SQL function, don't quote it
                $c = self::isSupportedFunction($col) ? $col :  $this->quoteId($col);
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
        if (($this->isSqlsrv()) && (($this->limit !== null) || ($this->offset !== null))) {
            if ($this->orderBy === null) {
                throw new Exception(
                    'Error: You must set an order by clause to execute a limit clause on the MS SQL Server database.'
                );
            }
            $sql .= $this->buildSqlSrvLimitAndOffset();
        // Else, if there is a nested SELECT statement.
        } else if (($this->table instanceof \Pop\Db\Sql) && ($this->table->hasSelect())) {
            $sql .= (string)$this->table->select();
        // Else, if there is a nested SELECT statement.
        } else if ($this->table instanceof \Pop\Db\Sql\Select) {
            $sql .= (string)$this->table;
        // Else, if there is an aliased table
        } else if (is_array($this->table)) {
            if (count($this->table) !== 1) {
                throw new Exception('Error: Only one table can be used in FROM clause.');
            }
            $alias = array_key_first($this->table);
            $table = $this->table[$alias];
            $sql  .= $this->quoteId($table) . ' AS ' . $this->quoteId($alias);
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

        // Build WHERE clause
        if ($this->where !== null) {
            $sql .= ' WHERE ' . $this->where;
        }

        // Build HAVING clause
        if ($this->having !== null) {
            $sql .= ' HAVING ' . $this->having;
        }

        // Build GROUP BY clause
        if ($this->groupBy !== null) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        // Build ORDER BY clause
        if ($this->orderBy !== null) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }

        // Build LIMIT clause for all other database types.
        if (!$this->isSqlsrv()) {
            if ($this->limit !== null) {
                if ((str_contains($this->limit, ',')) && ($this->isPgsql())) {
                    [$offset, $limit] = explode(',', $this->limit);
                    $this->offset     = (int)trim($offset);
                    $this->limit      = (int)trim($limit);
                }
                $sql .= ' LIMIT ' . $this->limit;
            }
        }

        // Build OFFSET clause for all other database types.
        if (!$this->isSqlsrv()) {
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        if ($this->alias !== null) {
            $sql = '(' . $sql . ') AS ' . $this->quoteId($this->alias);
        }

        return $sql;
    }

    /**
     * Render the SELECT statement
     *
     * @throws Exception
     * @return string
     */
    public function __toString(): string
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
    public function __get(string $name): mixed
    {
        switch (strtolower($name)) {
            case 'where':
                if ($this->where === null) {
                    $this->where = new Where($this);
                }
                return $this->where;
                break;
            case 'having':
                if ($this->having === null) {
                    $this->having = new Having($this);
                }
                return $this->having;
                break;
            default:
                throw new Exception("The property '" . $name ."' is not a valid property for this select object.");
        }
    }

    /**
     * Method to get the limit and offset
     *
     * @return array
     */
    protected function getLimitAndOffset(): array
    {
        $result = [
            'limit'  => null,
            'offset' => null
        ];

        // Calculate the limit and/or offset
        if ($this->offset !== null) {
            $result['offset'] = (int)$this->offset + 1;
            $result['limit']  = ($this->limit !== null) ? (int)$this->limit + (int)$this->offset : 0;
        } else if (str_contains($this->limit, ',')) {
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
    protected function buildSqlSrvLimitAndOffset(): string
    {
        $sql    = null;
        $result = $this->getLimitAndOffset();
        if ($result['offset'] !== null) {
            if ($this->where === null) {
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