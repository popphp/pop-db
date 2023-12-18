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
 * Update class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.5.0
 */
class Update extends AbstractPredicateClause
{

    /**
     * Set a value
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Update
     */
    public function set(string $name, mixed $value): Update
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
    public function values(array $values): Update
    {
        $this->setValues($values);
        return $this;
    }

    /**
     * Render the UPDATE statement
     *
     * @return string
     */
    public function render(): string
    {
        // Start building the UPDATE statement
        $sql = 'UPDATE ' . $this->quoteId($this->table) . ' SET ';
        $set = [];

        $paramCount = 1;
        $dbType = $this->getDbType();

        foreach ($this->values as $column => $value) {
            $colValue = (str_contains($column, '.')) ?
                substr($column, (strpos($column, '.') + 1)) : $column;

            $val = ($value === null) ? 'NULL' : $this->quote($value);
            $set[] = $this->quoteId($column) .' = ' . $val;
        }

        $sql .= implode(', ', $set);

        // Build any WHERE clauses
        if ($this->where !== null) {
            $sql .= ' WHERE ' . $this->where;
        }

        return $sql;
    }

    /**
     * Render the UPDATE statement
     *
     * @return string
     */
    public function __toString(): string
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
    public function __get(string $name): mixed
    {
        switch (strtolower($name)) {
            case 'where':
                if ($this->where === null) {
                    $this->where = new Where($this);
                }
                return $this->where;
                break;
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

}