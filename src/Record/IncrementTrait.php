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
     * Get next increment value
     *
     * @param  mixed  $columns
     * @param  int    $start
     * @param  string $field
     * @return int
     */
    public static function next(array|PredicateSet $columns, int $start = 1, string $field = 'row_id'): int
    {
        $currentIndex = (int)static::findOne($columns, ['order' => $field . ' DESC'])->{$field};
        return (!empty($currentIndex) ? ($currentIndex + 1) : $start);
    }

}
