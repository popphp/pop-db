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
 * Update class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Update extends AbstractSql
{

    /**
     * Access the where clause
     *
     */
    public function where()
    {

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
        return '';
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

}