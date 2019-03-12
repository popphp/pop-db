<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractProfiler implements ProfilerInterface
{

    /**
     * Step start time
     * @var float
     */
    protected $start = null;

    /**
     * Step finish time
     * @var float
     */
    protected $finish = null;

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
     * @return float
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Finish profiler
     *
     * @return AbstractProfiler
     */
    public function finish()
    {
        $this->finish = microtime(true);
        return $this;
    }

    /**
     * Get finish
     *
     * @return float
     */
    public function getFinish()
    {
        return $this->finish;
    }

    /**
     * Get elapsed time
     *
     * @return string
     */
    public function getElapsed()
    {
        if (null === $this->finish) {
            $this->finish();
        }
        return number_format(($this->finish - $this->start), 5);
    }

}