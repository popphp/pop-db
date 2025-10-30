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
namespace Pop\Db\Sql\Migration;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Db SQL migrator interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
interface MigratorInterface
{

    /**
     * Get the DB adapter
     *
     * @return AbstractAdapter
     */
    public function getDb(): AbstractAdapter;

    /**
     * Get the DB adapter (alias method)
     *
     * @return AbstractAdapter
     */
    public function db(): AbstractAdapter;

}
