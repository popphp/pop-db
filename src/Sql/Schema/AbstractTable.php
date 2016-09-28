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

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\AbstractSql;

/**
 * Abstract schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractTable extends AbstractSql
{

    protected $name = null;

    /**
     * Constructor
     *
     * Instantiate the table object
     *
     * @param  string          $name
     * @param  AbstractAdapter $db
     */
    public function __construct($name, $db)
    {
        $this->name = $name;
        parent::__construct($db);
    }

    public function getName()
    {
        return $this->name;
    }

    abstract public function render();

    abstract public function __toString();

}