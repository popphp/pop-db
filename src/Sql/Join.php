<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

use Pop\Db\Sql\Parser\Expression;
use Pop\Db\Sql\Parser\Operator;

/**
 * Join class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
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
     * @var ?AbstractSql
     */
    protected ?AbstractSql $sql = null;

    /**
     * Foreign table
     * @var ?string
     */
    protected ?string $foreignTable = null;

    /**
     * Columns
     * @var array
     */
    protected array $columns = [];

    /**
     * Join type
     * @var string
     */
    protected string $join = 'JOIN';

    /**
     * Constructor
     *
     * Instantiate the JOIN object
     *
     * @param  AbstractSql $sql
     * @param  mixed       $foreignTable
     * @param  array       $columns
     * @param  string      $join
     * @throws Exception
     */
    public function __construct(AbstractSql $sql, mixed $foreignTable, array $columns, string $join = 'JOIN')
    {
        $this->sql = $sql;

        // If it's a sub-select
        if (($foreignTable instanceof Select) || ($foreignTable instanceof \Pop\Db\Sql)) {
            $this->foreignTable = (string)$foreignTable;
        } else if (is_array($foreignTable)) {
            if (count($foreignTable) !== 1) {
                throw new Exception('Error: Only one table can be used in JOIN clause.');
            }
            $alias = array_key_first($foreignTable);
            $table = $foreignTable[$alias];
            $this->foreignTable = $this->sql->quoteId($table) . ' AS ' . $this->sql->quoteId($alias);
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
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get JOIN type
     *
     * @return string
     */
    public function getJoin(): string
    {
        return $this->join;
    }

    /**
     * Render JOIN
     *
     * @return string
     */
    public function render(): string
    {
        $columns = [];

        foreach ($this->columns as $column1 => $column2) {
            if (Expression::isShorthand($column1)) {
                ['column' => $column1, 'operator' => $operator] = Operator::parse($column1);
                if (($column2 === null) && ($operator == 'NOT')) {
                    $operator = 'IS ' . $operator;
                }
            } else {
                $operator = ($column2 === null) ? 'IS' : '=';
            }
            $operator = ' ' . $operator . ' ';

            if (is_array($column2)) {
                foreach ($column2 as $c) {
                    if ($c === null) {
                        $c = 'NULL';
                    } else if (is_string($c) && str_contains($c, '.')) {
                        $c = $this->sql->quoteId($c);
                    }
                    $columns[] = ((str_contains($column1, '.')) ? $this->sql->quoteId($column1) : $column1) . $operator . $c;
                }
            } else {
                if ($column2 === null) {
                    $column2 = 'NULL';
                } else if (is_string($column2)) {
                    $column2 = $this->sql->quoteId($column2);
                }
                $columns[] = $this->sql->quoteId($column1) . $operator . $column2;
            }
        }

        return $this->join . ' ' . $this->foreignTable . ' ON (' . implode(' AND ', $columns) . ')';
    }

    /**
     * Return JOIN as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
