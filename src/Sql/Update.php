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
 * Update class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Update extends AbstractClause
{

    /**
     * WHERE predicate object
     * @var Where
     */
    protected $where = null;

    /**
     * Access the WHERE clause
     *
     * @param  mixed $where
     * @return Update
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
     * @return Update
     */
    public function andWhere($where = null)
    {
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
     * @return Update
     */
    public function orWhere($where = null)
    {
        if ($this->where->hasPredicates()) {
            $this->where->getLastPredicateSet()->setCombine('OR');
        }
        $this->where($where);
        return $this;
    }

    /**
     * Set a value
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Update
     */
    public function set($name, $value)
    {
        $this->addNamedValue($name, $value);
        return $this;
    }

    /**
     * Set a value
     *
     * @param  array $values
     * @return Update
     */
    public function values(array $values)
    {
        $this->setValues($values);
        return $this;
    }

    /**
     * Render the UPDATE statement
     *
     * @return string
     */
    public function render()
    {
        // Start building the UPDATE statement
        $sql = 'UPDATE ' . $this->quoteId($this->table) . ' SET ';
        $set = [];

        $paramCount = 1;
        $dbType = $this->getDbType();

        foreach ($this->values as $column => $value) {
            $colValue = (strpos($column, '.') !== false) ?
                substr($column, (strpos($column, '.') + 1)) : $column;

            // Check for named parameters
            if ((':' . $colValue == substr($value, 0, strlen(':' . $colValue))) && ($dbType !== self::SQLITE)) {
                if (($dbType == self::MYSQL) || ($dbType == self::SQLSRV)) {
                    $value = '?';
                } else if (($dbType == self::PGSQL) && (!($this->db instanceof \Pop\Db\Adapter\Pdo))) {
                    $value = '$' . $paramCount;
                    $paramCount++;
                }
            }
            $val = (null === $value) ? 'NULL' : $this->quote($value);
            $set[] = $this->quoteId($column) .' = ' . $val;
        }

        $sql .= implode(', ', $set);

        // Build any WHERE clauses
        if (null !== $this->where) {
            $sql .= ' WHERE ' . $this->where->render($paramCount);
        }

        return $sql;
    }

    /**
     * Render the UPDATE statement
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Magic method to access $where property
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
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

}