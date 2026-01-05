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
namespace Pop\Db\Record;

use Pop\Crypt\Hashing\Hasher;
use Pop\Crypt\Encryption\Encrypter;
use Pop\Db\Sql\PredicateSet;

/**
 * Increment trait
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
trait IncrementTrait
{

    /**
     * Increment field
     * @var string
     */
    protected static string $incrementField = 'row_id';

    /**
     * Get next increment value
     *
     * @param  mixed $columns
     * @return int
     */
    public static function next(array|PredicateSet $columns): int
    {
        return ((int)static::findOne($columns, ['order' => static::$incrementField . ' DESC'])->{static::$incrementField} + 1);
    }

}
