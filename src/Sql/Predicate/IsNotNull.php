<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractSql;

/**
 * Is Not Null predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class IsNotNull extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the IS NOT NULL predicate set object
     *
     * @param  string  $values
     * @param  string $conjunction
     */
    public function __construct($values, $conjunction = 'AND')
    {
        $this->format = '%1 IS NOT NULL';
        parent::__construct($values, $conjunction);
    }

    /**
     * Render the predicate string
     *
     *
     * @param  AbstractSql $sql
     * @return string
     */
    public function render(AbstractSql $sql)
    {
        return '(' . str_replace('%1', $sql->quoteId($this->values), $this->format) . ')';
    }

}