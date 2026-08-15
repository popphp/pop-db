<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 *
 * @property-read ?PredicateSet $having HAVING predicate object (lazily created)
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
                $tokens = Parser\Keyword::split($having);
                if (count($tokens) > 1) {
                    foreach ($tokens as $i => $token) {
                        if (($i > 0) && (($tokens[$i - 1] === 'AND') || ($tokens[$i - 1] === 'OR'))) {
                            if ($tokens[$i - 1] === 'AND') {
                                $this->having->and($token);
                            } else {
                                $this->having->or($token);
                            }
                        } else if (($token !== 'AND') && ($token !== 'OR')) {
                            $this->having->add($token);
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
     * Quote a single GROUP BY / ORDER BY column
     *
     * A JsonExtract value object already carries its own fully-rendered, dialect-specific
     * extraction SQL, so it is embedded verbatim - passing it through trim()/quoteId() would
     * either error or wrap the whole expression in identifier quotes, producing invalid SQL.
     *
     * @param  mixed $column
     * @return string
     */
    protected function quoteByColumn(mixed $column): string
    {
        return ($column instanceof JsonExtract) ? (string)$column : $this->quoteId(trim($column));
    }

    /**
     * Set the GROUP BY value
     *
     * @param mixed $by
     * @return Select
     */
    public function groupBy(mixed $by): Select
    {
        if ($by instanceof JsonExtract) {
            $this->groupBy = (string)$by;
        } else if (is_array($by)) {
            $this->groupBy = implode(', ', array_map([$this, 'quoteByColumn'], $by));
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

        if ($by instanceof JsonExtract) {
            $byColumns = (string)$by;
        } else if (is_array($by)) {
            $byColumns = implode(', ', array_map([$this, 'quoteByColumn'], $by));
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
        $sql  = 'SELECT ' . (($this->distinct) ? 'DISTINCT ' : null);
        $sql .= $this->buildColumnsClause();
        $sql .= 'FROM ' . $this->buildFromClause();
        $sql .= $this->buildJoinsClause();

        // Build WHERE clause
        if ($this->wherePredicate !== null) {
            $sql .= ' WHERE ' . $this->wherePredicate;
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

        $sql .= $this->buildLimitOffsetClause();

        if ($this->alias !== null) {
            $sql = '(' . $sql . ') AS ' . $this->quoteId($this->alias);
        }

        return $sql;
    }

    /**
     * Build the column list portion of the SELECT statement
     *
     * @return string
     */
    protected function buildColumnsClause(): string
    {
        if (count($this->values) === 0) {
            return '* ';
        }

        $cols = [];
        foreach ($this->values as $as => $col) {
            // If column is a nested SQL query
            if ($col instanceof AbstractSql) {
                $cols[] = (!is_numeric($as)) ?
                    '(' . $col . ') AS ' . $this->quoteId($as) : '(' .  $col . ')';
            } else if ($col instanceof JsonExtract) {
                $cols[] = (!is_numeric($as)) ?
                    (string)$col . ' AS ' . $this->quoteId($as) : (string)$col;
            } else {
                // If column is a SQL function, don't quote it
                $c = self::isSupportedFunction($col) ? $col :  $this->quoteId($col);
                $cols[] = (!is_numeric($as)) ?
                    $c . ' AS ' . $this->quoteId($as) : $c;
            }
        }

        return implode(', ', $cols) . ' ';
    }

    /**
     * Build the FROM clause target (table, aliased table, or nested SELECT)
     *
     * @throws Exception
     * @return string
     */
    protected function buildFromClause(): string
    {
        // Account for LIMIT and OFFSET clauses if the database is SQLSRV
        if (($this->isSqlsrv()) && (($this->limit !== null) || ($this->offset !== null))) {
            if ($this->orderBy === null) {
                throw new Exception(
                    'Error: You must set an order by clause to execute a limit clause on the MS SQL Server database.'
                );
            }
            return $this->buildSqlSrvLimitAndOffset();
        }

        // Nested SELECT statement
        if (($this->table instanceof \Pop\Db\Sql) && ($this->table->hasSelect())) {
            return (string)$this->table->select();
        }

        // Nested SELECT statement
        if ($this->table instanceof \Pop\Db\Sql\Select) {
            return (string)$this->table;
        }

        // Aliased table
        if (is_array($this->table)) {
            if (count($this->table) !== 1) {
                throw new Exception('Error: Only one table can be used in FROM clause.');
            }
            $alias = array_key_first($this->table);
            $table = $this->table[$alias];
            return $this->quoteId($table) . ' AS ' . $this->quoteId($alias);
        }

        return $this->quoteId($this->table);
    }

    /**
     * Build any JOIN clauses
     *
     * @return string
     */
    protected function buildJoinsClause(): string
    {
        $sql = '';

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join;
        }

        return $sql;
    }

    /**
     * Build the LIMIT/OFFSET clause for non-SQLSRV databases
     *
     * @return string
     */
    protected function buildLimitOffsetClause(): string
    {
        if ($this->isSqlsrv()) {
            return '';
        }

        $sql = '';

        if ($this->limit !== null) {
            if ((is_string($this->limit)) && (str_contains($this->limit, ',')) && ($this->isPgsql())) {
                [$offset, $limit] = explode(',', $this->limit);
                $this->offset     = (int)trim($offset);
                $this->limit      = (int)trim($limit);
            }
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
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
                if ($this->wherePredicate === null) {
                    $this->wherePredicate = new Where($this);
                }
                return $this->wherePredicate;
            case 'having':
                if ($this->having === null) {
                    $this->having = new Having($this);
                }
                return $this->having;
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
        $sql    = '';
        $result = $this->getLimitAndOffset();
        if ($result['offset'] !== null) {
            if ($this->wherePredicate === null) {
                $this->wherePredicate = new Where($this);
            }

            $sql .= '(SELECT *, ROW_NUMBER() OVER (ORDER BY ' . $this->orderBy . ') AS RowNumber FROM ' .
                $this->quoteId($this->table) . ') AS OrderedTable';
            if ($result['limit'] > 0) {
                $this->wherePredicate->between('OrderedTable.RowNumber', $result['offset'], $result['limit']);
            } else {
                $this->wherePredicate->greaterThanOrEqualTo('OrderedTable.RowNumber', $result['offset']);
            }
        } else {
            $sql  = str_replace('SELECT', 'SELECT TOP ' . $result['limit'], $sql);
            $sql .= $this->quoteId($this->table);
        }

        return $sql;
    }

}
