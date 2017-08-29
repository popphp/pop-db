<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter\Profiler;

/**
 * MySQL database adapter profiler class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Profiler
{

    /**
     * Profiler start time
     * @var float
     */
    protected $start = null;

    /**
     * Profiler finish time
     * @var float
     */
    protected $finish = null;

    /**
     * Profiler current index
     * @var int
     */
    protected $current = 0;

    /**
     * Profiler steps
     * @var array
     */
    protected $steps = [];

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
     * Add step
     *
     * @param  Step $step
     * @return Profiler
     */
    public function addStep(Step $step = null)
    {
        if (null === $step) {
            $step = new Step();
        }
        $this->steps[] = $step;
        $this->current = count($this->steps) - 1;

        return $this;
    }

    /**
     * Get steps
     *
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Get current step
     *
     * @return Step
     */
    public function getCurrentStep()
    {
        return (isset($this->steps[$this->current])) ? $this->steps[$this->current] : null;
    }

    /**
     * Finish profiler
     *
     * @return Profiler
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

    /**
     * Magic method to support shorthand calls to certain values in the profiler
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'start':
                return $this->start;
                break;
            case 'finish':
                return $this->finish;
                break;
            case 'current':
                return $this->getCurrentStep();
                break;
            case 'elapsed':
                return $this->getElapsed();
                break;
            default:
                return null;
        }
    }

}