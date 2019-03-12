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
 * Abstract clause class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractClause extends AbstractSql
{

    /**
     * Table
     * @var mixed
     */
    protected $table = null;

    /**
     * Alias
     * @var string
     */
    protected $alias = null;

    /**
     * Values
     * @var array
     */
    protected $values = [];

    /**
     * Set the table
     *
     * @param  mixed $table
     * @return AbstractSql
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Determine if there is an alias
     *
     * @return boolean
     */
    public function hasAlias()
    {
        return (null !== $this->alias);
    }

    /**
     * Get the alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set the alias
     *
     * @param  string $alias
     * @return AbstractSql
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the values
     *
     * @param  array $values
     * @return AbstractSql
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Add a value
     *
     * @param  mixed $value
     * @return AbstractSql
     */
    public function addValue($value)
    {
        if (!is_array($value) && !is_object($value)) {
            $this->values[] = $value;
        }
        return $this;
    }

    /**
     * Add a named value
     *
     * @param  string $name
     * @param  mixed  $value
     * @return AbstractSql
     */
    public function addNamedValue($name, $value)
    {
        if (!is_array($value) && !is_object($value)) {
            $this->values[$name] = $value;
        }
        return $this;
    }

    /**
     * Get the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Get a value
     *
     * @param  string $name
     * @return mixed
     */
    public function getValue($name)
    {
        return (isset($this->values[$name])) ? $this->values[$name] : null;
    }

    /**
     * Render the statement
     *
     * @return string
     */
    abstract public function render();

}