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
namespace Pop\Db\Sql\Schema;

/**
 * Schema CREATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Create extends AbstractStructure
{

    protected $ifNotExists = false;

    public function ifNotExists()
    {
        $this->ifNotExists = true;
        return $this;
    }

    public function render()
    {
        $sql = 'CREATE TABLE ' . ((($this->ifNotExists) && ($this->dbType != self::SQLSRV)) ? 'IF NOT EXISTS ' : null) .
            $this->quoteId($this->name) . ' (' . PHP_EOL;

        $sql .= ');';

        return $sql;
    }

    public function __toString()
    {
        return $this->render();
    }

}