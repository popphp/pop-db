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
 * Db abstract adapter profiler class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.5.0
 */
abstract class AbstractProfiler implements ProfilerInterface
{

    /**
     * Step start time
     * @var ?float
     */
    protected ?float $start = null;

    /**
     * Step finish time
     * @var ?float
     */
    protected ?float $finish = null;

    /**
     * Constructor
     *
     * Instantiate the profiler object
     */
    public function __construct()
    {
        $this->start = microtime(true);
    }

    /**
     * Get start
     *
     * @return float|null
     */
    public function getStart(): float|null
    {
        return $this->start;
    }

    /**
     * Finish profiler
     *
     * @return AbstractProfiler
     */
    public function finish(): AbstractProfiler
    {
        $this->finish = microtime(true);
        return $this;
    }

    /**
     * Get finish
     *
     * @return float|null
     */
    public function getFinish(): float|null
    {
        return $this->finish;
    }

    /**
     * Get elapsed time
     *
     * @return string
     */
    public function getElapsed(): string
    {
        if ($this->finish === null) {
            $this->finish();
        }
        return number_format(($this->finish - $this->start), 5);
    }

}
