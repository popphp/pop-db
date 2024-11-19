<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */

namespace Pop\Db\Adapter\Transaction;

/**
 * Nested transaction manager for use in AdapaterInterface implementations
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @author     Martok <martok@martoks-place.de>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.5.0
 */
class Manager extends AbstractManager
{

    /**
     * Transaction state flag
     * @var int
     */
    private int $transactionState = 0;
    private const TS_NONE = 0;
    private const TS_OPEN = 1;
    private const TS_ROLLED_BACK = -1;

    /**
     * Transaction depth
     * @var int
     */
    private int $transactionDepth = 0;

    /**
     * Use savepoints or simulated nested transactions (SNTs)
     * @var bool
     */
    private bool $useSavepoints;

    /**
     * Names of active savepoints. Count is always one less than $transactionDepth.
     * @var string[]
     */
    private array $savepoints = [];
    private int $savepointName = 0;

    /**
     * Constructor
     *
     * Instantiate the transaction manager object
     *
     * @param bool $useSavepoints Enable the use of savepoints by default
     */
    public function __construct(bool $useSavepoints = true)
    {
        $this->useSavepoints = $useSavepoints;
    }

    /**
     * Check if adapter is in the middle of an open transaction
     *
     * @return bool
     */
    public function isTransaction(): bool
    {
        return $this->transactionState !== self::TS_NONE;
    }

    /**
     * Get transaction depth
     *
     * @return int
     */
    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }

    /**
     * Enter a new transaction or increase nesting level
     *
     * @param ?callable $beginFunc Called when a new top-level transaction must be started
     * @param ?callable $savepointFunc Called when a named savepoint is created
     * @return bool
     */
    public function enter(?callable $beginFunc = null, ?callable $savepointFunc = null): bool
    {
        $this->transactionDepth++;

        // an already rolled back SNT can never turn back into a normal one
        if ($this->transactionState == self::TS_ROLLED_BACK)
            return false;

        if ($this->transactionDepth == 1) {
            // BEGIN a new transaction
            if (is_callable($beginFunc)) {
                $beginFunc();
            }
            $this->transactionState = self::TS_OPEN;
        } else {
            // increase nesting level
            if ($this->useSavepoints && is_callable($savepointFunc)) {
                try {
                    $sp = 'PopDbTxn_' . $this->savepointName++;
                    $savepointFunc($sp);
                    $this->savepoints[] = $sp;
                } catch (\Exception $e) {
                    // if this failed, assume this Adapter doesn't actually support savepoints
                    $this->useSavepoints = false;
                }
            }
        }
        return true;
    }

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
                          ?callable $savepointReleaseFunc = null, ?callable $savepointRollbackFunc = null): bool
    {
        if ($this->transactionDepth <= 0 || $this->transactionState == self::TS_NONE)
            return false;

        $this->transactionDepth--;

        // Leaving the outermost transaction always commits/rolls back the transaction.
        // If savepoints are enabled, leaving a nested transaction requires the rollback/release of the savepoint.
        // Without savepoints, only the outermost transaction is real, becoming an automatic rollback if
        // any nested transaction was a rollback.
        if ($this->useSavepoints && $this->transactionDepth > 0) {
            $sp = array_pop($this->savepoints);
            if ($doCommit) {
                if (is_callable($savepointReleaseFunc)) {
                    $savepointReleaseFunc($sp);
                }
            } else {
                if (is_callable($savepointRollbackFunc)) {
                    $savepointRollbackFunc($sp);
                }
            }
        } else {
            if (!$doCommit)
                $this->transactionState = self::TS_ROLLED_BACK;
        }

        if ($this->transactionDepth == 0) {
            if ($this->transactionState == self::TS_OPEN && is_callable($commitFunc))
                $commitFunc();
            elseif ($this->transactionState == self::TS_ROLLED_BACK && is_callable($rollbackFunc))
                $rollbackFunc();
            $this->transactionState = self::TS_NONE;
        }

        return true;
    }

}
