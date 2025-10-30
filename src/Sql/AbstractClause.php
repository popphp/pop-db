<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
abstract class AbstractClause extends AbstractSql
{

    /**
     * Table
     * @var mixed
     */
    protected mixed $table = null;

    /**
     * Alias
     * @var ?string
     */
    protected ?string $alias = null;

    /**
     * Values
     * @var array
     */
    protected array $values = [];

    /**
     * Set the table
     *
     * @param  mixed $table
     * @return AbstractClause
     */
    public function setTable(mixed $table): AbstractClause
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Determine if there is an alias
     *
     * @return bool
     */
    public function hasAlias(): bool
    {
        return ($this->alias !== null);
    }

    /**
     * Get the alias
     *
     * @return ?string
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Set the alias
     *
     * @param  string $alias
     * @return AbstractClause
     */
    public function setAlias(string $alias): AbstractClause
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Get the table
     *
     * @return ?string
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Set the values
     *
     * @param  array $values
     * @return AbstractClause
     */
    public function setValues(array $values): AbstractClause
    {
        foreach ($values as $column => $value) {
            if ($this->isParameter($value, $column)) {
                $values[$column] = $this->getParameter($value, $column);
            }
        }

        $this->values = $values;
        return $this;
    }

    /**
     * Add a value
     *
     * @param  mixed  $value
     * @param  ?string $name
     * @return AbstractClause
     */
    public function addValue(mixed $value, ?string $name = null): AbstractClause
    {
        if (!is_array($value) && (($value instanceof \Stringable) || !is_object($value))) {
            if ($this->isParameter($value, $name)) {
                $value = $this->getParameter($value, $name);
            }
            if ($name !== null) {
                $this->values[$name] = $value;
            } else {
                $this->values[] = $value;
            }
        }
        return $this;
    }

    /**
     * Get the values
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Get a value
     *
     * @param  string $name
     * @return mixed
     */
    public function getValue(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    /**
     * Render the statement
     *
     * @return string
     */
    abstract public function render(): string;

}
