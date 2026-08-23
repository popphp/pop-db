<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class IsNotNull extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the IS NOT NULL predicate set object
     *
     * @param  string $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(string $values, string $conjunction = 'AND')
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
    public function render(AbstractSql $sql): string
    {
        return '(' . str_replace('%1', $sql->quoteId($this->values), $this->format) . ')';
    }

}
