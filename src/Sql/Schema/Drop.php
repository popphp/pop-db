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
 * Schema DROP table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Drop extends AbstractTable
{

    protected $ifExists = false;

    public function ifExists()
    {
        $this->ifExists = true;
        return $this;
    }

    public function render()
    {
        return 'DROP TABLE ' . ((($this->ifExists) && ($this->dbType != self::SQLSRV)) ? 'IF EXISTS ' : null)
        . $this->quoteId($this->name) . ';';
    }

    public function __toString()
    {
        return $this->render();
    }

}