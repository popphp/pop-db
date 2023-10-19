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
 * Abstract clause class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
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
     * Supported standard SQL aggregate functions
     * @var array
     */
    protected static array $aggregateFunctions = [
        'AVG', 'COUNT', 'MAX', 'MIN', 'SUM'
    ];

    /**
     * Supported standard SQL math functions
     * @var array
     */
    protected static array $mathFunctions = [
        'ABS', 'RAND', 'SQRT', 'POW', 'POWER', 'EXP', 'LN', 'LOG', 'LOG10', 'GREATEST', 'LEAST',
        'DIV', 'MOD', 'ROUND', 'TRUNC', 'CEIL', 'CEILING', 'FLOOR', 'COS', 'ACOS', 'ACOSH', 'SIN',
        'SINH', 'ASIN', 'ASINH', 'TAN', 'TANH', 'ATANH', 'ATAN2',
    ];

    /**
     * Supported standard SQL string functions
     * @var array
     */
    protected static array $stringFunctions = [
        'CONCAT', 'FORMAT', 'INSTR', 'LCASE', 'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD',
        'LTRIM', 'POSITION', 'QUOTE', 'REGEXP', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'RPAD',
        'RTRIM', 'SPACE', 'STRCMP', 'SUBSTRING', 'SUBSTR', 'TRIM', 'UCASE', 'UPPER'
    ];

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
     * @param  mixed $value
     * @return AbstractClause
     */
    public function addValue(mixed $value): AbstractClause
    {
        if (!is_array($value) && !is_object($value)) {
            if ($this->isParameter($value)) {
                $value = $this->getParameter($value);
            }
            $this->values[] = $value;
        }
        return $this;
    }

    /**
     * Add a named value
     *
     * @param  string $name
     * @param  mixed  $value
     * @return AbstractClause
     */
    public function addNamedValue(string $name, mixed $value): AbstractClause
    {
        if (!is_array($value) && !is_object($value)) {
            if ($this->isParameter($value, $name)) {
                $value = $this->getParameter($value, $name);
            }
            $this->values[$name] = $value;
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
     * Check if value contains a standard SQL supported function
     *
     * @param  string $value
     * @return bool
     */
    public static function isSupportedFunction(string $value): bool
    {
        if (str_contains($value, '(')) {
            $value = trim(substr($value, 0, strpos($value, '(')));
        }
        $value = strtoupper($value);

        return (in_array($value, static::$aggregateFunctions) ||
            in_array($value, static::$mathFunctions) ||
            in_array($value, static::$stringFunctions));
    }

    /**
     * Render the statement
     *
     * @return string
     */
    abstract public function render(): string;

}