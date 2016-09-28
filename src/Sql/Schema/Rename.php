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
 * Schema RENAME table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Rename extends AbstractTable
{

    protected $to = null;

    public function to($table)
    {
        $this->to = $table;
        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function render()
    {
        return ($this->dbType == self::MYSQL) ?
                'RENAME TABLE ' . $this->quoteId($this->table) . ' TO ' . $this->quoteId($this->to) . ';' . PHP_EOL :
                'ALTER TABLE ' . $this->quoteId($this->table) . ' RENAME TO ' . $this->quoteId($this->to) . ';' . PHP_EOL;
    }

    public function __toString()
    {
        return $this->render();
    }

}