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
namespace Pop\Db\Adapter\Profiler;

/**
 * Db adapter profiler interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
interface ProfilerInterface
{

    /**
     * Get start
     *
     * @return float|null
     */
    public function getStart(): float|null;

    /**
     * Finish profiler
     *
     * @return ProfilerInterface
     */
    public function finish(): ProfilerInterface;

    /**
     * Get end
     *
     * @return float|null
     */
    public function getFinish(): float|null;

    /**
     * Get elapsed time
     *
     * @return string|null
     */
    public function getElapsed(): string|null;

}
