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
namespace Pop\Db\Adapter\Transaction;

/**
 * Db adapter transaction manager interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @author     Martok <martok@martoks-place.de>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
interface ManagerInterface
{

    /**
     * Check if adapter is in the middle of an open transaction
     *
     * @return bool
     */
    public function isTransaction(): bool;

    /**
     * Get transaction depth
     *
     * @return int
     */
    public function getTransactionDepth(): int;

    /**
     * Enter a new transaction or increase nesting level
     *
     * @param ?callable $beginFunc Called when a new top-level transaction must be started
     * @param ?callable $savepointFunc Called when a named savepoint is created
     * @return bool
     */
    public function enter(?callable $beginFunc = null, ?callable $savepointFunc = null): bool;

    /**
     * Leave a transaction or reduce nesting level
     *
     * @param bool $doCommit If true, perform a commit. Rollback otherwise.
     * @param ?callable $commitFunc Called when a top-level commit must be performed
     * @param ?callable $rollbackFunc Called when a top-level rollback must be performed
     * @param ?callable $savepointReleaseFunc Called when a savepoint is released (like commit)
     * @param ?callable $savepointRollbackFunc Called when the transaction is rolled back to a savepoint
     * @return bool
     */
    public function leave(bool      $doCommit,
                          ?callable $commitFunc = null, ?callable $rollbackFunc = null,
                          ?callable $savepointReleaseFunc = null, ?callable $savepointRollbackFunc = null): bool;

}
